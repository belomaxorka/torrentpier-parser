<?php
/**
 * --------------------------------------------------------
 * Парсер раздач. Специально для TorrentPier
 *
 * @link https://torrentpier.com/
 * @license MIT License
 * @author Участники torrentpier.com, Ральф, belomaxorka
 * --------------------------------------------------------
 */

define('BB_SCRIPT', 'release');
define('BB_ROOT', './');
require_once __DIR__ . '/common.php';
require_once INC_DIR . '/parser/trackers.php';
require_once INC_DIR . '/parser/functions.php';
require_once INC_DIR . '/parser/curl/CurlHttpClient.php';
require_once INC_DIR . '/parser/random_user_agent/UserAgent.php';
require_once INC_DIR . '/bbcode.php';

set_time_limit(60);
$hidden_form_fields = $message = $subject = '';

// Вводимый URL адрес пользователем
$url = isset($_POST['url']) ? htmlCHR($_POST['url']) : '';

// Форум в который сохранять раздачи
$forum_id = (int)request_var('forum_id', '');

// Start session management
$user->session_start(array('req_login' => ($bb_cfg['torrent_parser']['parser_auth'] !== 'guest')));

// Получаем путь до папки с торрентами
$attach_dir = get_attachments_dir();

/**
 * ------------------------------------------------------
 * Проверка расширений
 * ------------------------------------------------------
 */
$ext_errors = array();
$ext_list = array('dom', 'mbstring', 'curl', 'iconv');
foreach ($ext_list as $ext) {
	if (!extension_loaded($ext)) {
		$ext_errors[] = $ext;
	}
}
if (!empty($ext_errors)) {
	bb_die(sprintf($lang['PARSER_MISSING_EXTENSIONS'], implode(', ', $ext_errors)));
}
unset($ext_errors, $ext_list);

/**
 * ------------------------------------------------------
 * Проверка авторизации
 * ------------------------------------------------------
 */
if ($bb_cfg['torrent_parser']['parser_auth'] === 'user' && (IS_GROUP_MEMBER && !empty($bb_cfg['torrent_parser']['group_id']))) {
	// Проверка на участника группы
	$groups = implode(',', $bb_cfg['torrent_parser']['group_id']);
	$vip = DB()->fetch_row("SELECT user_id FROM  " . BB_USER_GROUP . " WHERE group_id IN($groups) AND user_id = " . $userdata['user_id']);
	if (!$vip) {
		bb_die($lang['PARSER_NO_GROUP_RIGHTS']);
	}
	unset($groups, $vip);
} else {
	// Проверка на наличие доступа
	if (in_array($bb_cfg['torrent_parser']['parser_auth'], array('both', 'admin', 'moderator', 'user'))) {
		if ((!IS_AM && $bb_cfg['torrent_parser']['parser_auth'] === 'both') ||
			(!IS_ADMIN && $bb_cfg['torrent_parser']['parser_auth'] === 'admin') ||
			(!IS_MOD && !IS_ADMIN && $bb_cfg['torrent_parser']['parser_auth'] === 'moderator') ||
			(IS_GUEST && $bb_cfg['torrent_parser']['parser_auth'] === 'user')
		) {
			bb_die($lang['NOT_AUTHORISED']);
		}
	} elseif ($bb_cfg['torrent_parser']['parser_auth'] !== 'guest') {
		bb_die($lang['NOT_AUTHORISED']);
	}
}

/**
 * ------------------------------------------------------
 * Формирование главной страницы
 * ------------------------------------------------------
 */
if (empty($url)) {
	// Получаем все форумы
	if (!$forums = $datastore->get('cat_forums')) {
		$datastore->update('cat_forums');
		$forums = $datastore->get('cat_forums');
	}
	$cat_title_html = $forums['cat_title_html'];
	$forum_name_html = $forums['forum_name_html'];

	$excluded_forums_csv = $user->get_excluded_forums(AUTH_READ);
	$allowed_forums = array_diff(explode(',', $forums['tracker_forums']), explode(',', $excluded_forums_csv));

	if (!$allowed_forums) {
		bb_die($lang['PARSER_NO_ALLOWED_FORUMS']);
	}

	$cat_forum = array();
	foreach ($allowed_forums as $forum_id) {
		$f = $forums['f'][$forum_id];
		$cat_forum['c'][$f['cat_id']][] = $forum_id;

		if ($f['forum_parent']) {
			$cat_forum['subforums'][$forum_id] = true;
			$cat_forum['forums_with_sf'][$f['forum_parent']] = true;
		}
	}
	unset($forums);
	$datastore->rm('cat_forums');

	// Формируем список форумов
	$opt = '';
	foreach ($cat_forum['c'] as $cat_id => $forums_ary) {
		$opt .= '<optgroup label="&nbsp;' . $cat_title_html[$cat_id] . "\">\n";

		foreach ($forums_ary as $forum_id) {
			$forum_name = $forum_name_html[$forum_id];
			$forum_name = str_short($forum_name, 58);
			$style = '';
			if (!isset($cat_forum['subforums'][$forum_id])) {
				$class = 'root_forum';
				$class .= isset($cat_forum['forums_with_sf'][$forum_id]) ? ' has_sf' : '';
				$style = " class=\"$class\"";
			}
			$selected = (isset($search_in_forums_fary[$forum_id])) ? HTML_SELECTED : '';
			$opt .= '<option id="fs-' . $forum_id . '" value="' . $forum_id . '"' . $style . $selected . '>' . (isset($cat_forum['subforums'][$forum_id]) ? HTML_SF_SPACER : '') . $forum_name . "&nbsp;</option>\n";
		}

		$opt .= "</optgroup>\n";
	}
	$search_all_opt = '<option disabled value="0">&nbsp;' . htmlCHR($lang['ALL_AVAILABLE']) . "</option>\n";
	$cat_forum_select = "\n<select class=\"form-control form-control-sm\" id=\"fs\" name=\"forum_id\" style=\"font-size: small;\">\n" . $search_all_opt . $opt . "</select>\n";

	$supported_trackers = array();
	foreach ($trackers as $tracker) {
		if (!isset($tracker['info']) || !is_array($tracker['info'])) {
			continue;
		}
		$icon = $href = '';
		$tracker = $tracker['info'];

		if (!empty($tracker['icon'])) {
			$icon = '&nbsp;<img style="width: 20px; height: 20px;" alt="' . $tracker['name'] . '" src="' . $tracker['icon'] . '">';
		}

		$tracker_name = '<div style="display: inline-flex; align-items: center; justify-content: center;"><span>' . $tracker['name'] . '</span>' . $icon . '</div>';
		if (!empty($tracker['href'])) {
			$supported_trackers[] = '<a target="_blank" href="' . $tracker['href'] . '">' . $tracker_name . '</a>';
		} else {
			$supported_trackers[] = $tracker_name;
		}
	}

	$template->assign_vars(array(
		'IN_PARSER' => true,
		'SUPPORTED_TRACKERS' => !empty($supported_trackers) ? implode(', ', $supported_trackers) : false,
		'SELECT_FORUM' => $cat_forum_select,
	));
} else {
	// Проверка ссылки на корректность
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		die_and_refresh($lang['PARSER_INVALID_URL']);
	}
	$url = preg_replace('/^(https?:\/\/)(www\.)(.*)$/', '$1$3', $url);

	// Инициализация библиотеки для обращений
	$curl = new \Dinke\CurlHttpClient;
	$curl->setUserAgent(\Campo\UserAgent::random(array('agent_type' => 'Browser'))); // Случайный User-Agent
	// Настройка прокси
	if ($bb_cfg['torrent_parser']['proxy']['enabled']) {
		$curl->setProxy($bb_cfg['torrent_parser']['proxy']['url'], $bb_cfg['torrent_parser']['proxy']['use_socks5']);
		if (!empty($bb_cfg['torrent_parser']['proxy']['auth'])) {
			$curl->setProxyAuth($bb_cfg['torrent_parser']['proxy']['auth']);
		}
	}

	// Проверка вводимого URL адреса
	$tracker = null;
	$tracker_data = array();
	foreach ($trackers as $name => $data) {
		// Проверка настроек трекера
		if (!is_string($name)) {
			bb_die(sprintf($lang['PARSER_INVALID_TRACKER_CONFIG'], '*tracker name (key)*'));
		}
		if (empty($data) || !is_array($data)) {
			bb_die(sprintf($lang['PARSER_INVALID_TRACKER_CONFIG'], '*empty*'));
		}
		if (empty($data['settings']) || !is_array($data['settings'])) {
			bb_die(sprintf($lang['PARSER_INVALID_TRACKER_CONFIG'], 'settings'));
		}
		if (isset($data['auth']) && !is_array($data['auth'])) {
			bb_die(sprintf($lang['PARSER_INVALID_TRACKER_CONFIG'], 'auth'));
		}
		if (isset($data['redirect']) && !is_array($data['redirect'])) {
			bb_die(sprintf($lang['PARSER_INVALID_TRACKER_CONFIG'], 'redirect'));
		}

		// Проверка на редиректы
		if (!empty($data['redirect']['from'])) {
			foreach ($data['redirect']['from'] as $fromUrl) {
				if (!filter_var($data['redirect']['to'], FILTER_VALIDATE_URL) || !filter_var($fromUrl, FILTER_VALIDATE_URL)) {
					bb_die($lang['PARSER_INVALID_REDIRECT_URLS']);
				}
				if (strpos($url, $fromUrl) === 0) {
					$url = str_replace($fromUrl, $data['redirect']['to'], $url);
					break;
				}
			}
		}

		// Проверка по регулярному выражению
		if (preg_match($data['settings']['regex'], $url)) {
			if (!$data['enabled']) {
				// Парсинг с трекера отключен
				die_and_refresh(sprintf($lang['PARSER_TRACKER_DISABLED'], $name));
			}
			if ((isset($data['auth']) && $data['auth']['enabled']) && (empty($bb_cfg['torrent_parser']['auth'][$name]['login']) || empty($bb_cfg['torrent_parser']['auth'][$name]['pass']))) {
				// Неверные данные авторизации
				bb_die(sprintf($lang['PARSER_EMPTY_AUTH'], $name));
			}
			$tracker = $name; // Название трекера
			$tracker_data = $data; // Настройки трекера
			break;
		}
	}
	if ($tracker === null) {
		die_and_refresh(sprintf($lang['PARSER_INVALID_TRACKER'], $url));
	}

	// ----------------------- Обращение к трекеру -----------------------
	// Подключение парсера
	$tracker_file_path = INC_DIR . "/parser/trackers/$tracker/$tracker.php";
	if (!file_exists($tracker_file_path)) {
		bb_die(sprintf($lang['PARSER_CANT_FIND_PARSER'], hide_bb_path($tracker_file_path)));
	}
	require_once $tracker_file_path;

	// Авторизация
	if (isset($tracker_data['auth']) && $tracker_data['auth']['enabled']) {
		if (!filter_var($tracker_data['auth']['login_url'], FILTER_VALIDATE_URL)) {
			bb_die($lang['PARSER_EMPTY_AUTH_LINK']);
		}

		// Создание папки для куки
		if (!is_dir(COOKIES_PARS_DIR)) {
			if (!mkdir(COOKIES_PARS_DIR, 0777)) {
				die_and_refresh($lang['PARSER_CANT_CREATE_COOKIES_DIR']);
			}
		}

		// Сохранение куки
		$curl->storeCookies(COOKIES_PARS_DIR . '/' . $tracker . '_cookie.txt');

		// Отправка данных
		$submit_vars = array(
			$tracker_data['auth']['login_input_name'] => $bb_cfg['torrent_parser']['auth'][$tracker]['login'],
			$tracker_data['auth']['password_input_name'] => $bb_cfg['torrent_parser']['auth'][$tracker]['pass'],
			'login' => true,
			'autologin' => 'on',
		);
		$content = $curl->sendPostData($tracker_data['auth']['login_url'], $submit_vars);

		// Проверка на успешную авторизацию
		if (!empty($tracker_data['auth']['login_has_error_element'])) {
			if (isset($tracker_data['settings']['from_win_1251_iconv']) && $tracker_data['settings']['from_win_1251_iconv']) {
				$content = iconv('windows-1251', 'UTF-8', $content);
			} else {
				$content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content));
			}
			if (preg_match($tracker_data['auth']['login_has_error_element'], $content)) {
				// Ошибка авторизации
				bb_die($lang['PARSER_AUTH_ERROR']);
			}
		}

		unset($content);
	}

	// Получение содержимого
	$content = fetch_content($curl, $url, $tracker_data['settings']['target_element'], (isset($tracker_data['settings']['from_win_1251_iconv']) ? $tracker_data['settings']['from_win_1251_iconv'] : false));

	// Парсим HTML код страницы
	if ($message = $tracker($content, $curl, $tracker_data)) {
		$torrent_file = $message['torrent']; // Ссылка на торрент-файл / attach_id
		$subject = $message['title']; // Заголовок сообщения

		// Проверка ссылки на торрент
		if (empty($torrent_file)) {
			die_and_refresh($lang['PARSER_CANT_GET_TORRENT']);
		}

		// Проверка наличия заголовка
		if (empty($subject)) {
			die_and_refresh($lang['PARSER_CANT_GET_TITLE']);
		}

		// Получение торрент-файла
		$dl_url = !empty($tracker_data['settings']['dl_url']) ? $tracker_data['settings']['dl_url'] : '';
		$torrent = $curl->fetchUrl($dl_url . $torrent_file);
		$curl->close();

		// Декодирование торрент-файла
		$tor = torrent_decode($torrent, $info_hash);

		// Проверка на повтор
		duplicate_check($info_hash, $subject, $url);

		// Прикрепляем торрент-файл
		attach_torrent_file($tor, $torrent, $hidden_form_fields);
	}
	// -------------------------------------------------------------------

	// Формирование топика
	$hidden_form_fields .= '<input type="hidden" name="mode" value="newtopic" />';
	$hidden_form_fields .= '<input type="hidden" name="' . POST_FORUM_URL . '" value="' . $forum_id . '" />';

	generate_smilies('inline');
	$template->assign_vars(array(
		'POSTING_TYPE_TITLE' => $lang['POST_A_NEW_TOPIC'],
		'SUBJECT' => $subject,
		'MESSAGE' => $message['content'],
		'S_POST_ACTION' => 'posting.php',

		'IN_PM' => true,
		'POSTING_SUBJECT' => true,
		'S_HIDDEN_FORM_FIELDS' => $hidden_form_fields,
	));
}

$template->assign_vars(array(
	'PAGE_TITLE' => $lang['PARSER_TITLE'],
	'PROGRESS_BAR_IMG' => $images['progress_bar'],
));

print_page('posting.tpl');
