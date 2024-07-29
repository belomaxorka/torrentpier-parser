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
require_once INC_DIR . '/parser/functions.php';
require_once INC_DIR . '/parser/curl/CurlHttpClient.php';
require_once INC_DIR . '/parser/random_user_agent/UserAgent.php';
require_once INC_DIR . '/bbcode.php';

set_time_limit(120);
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
 * Список доступных трекеров
 * ------------------------------------------------------
 */
$trackers = array(
	'rutracker' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "/https:\/\/rutracker.org\/forum\/viewtopic.php\?t=/",
		'login_url' => 'https://rutracker.org/forum/login.php',
		'dl_url' => 'https://rutracker.org/forum/dl.php?t=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<div style="padding-top: 6px;">'
	),
	'rutor' => array(
		'enabled' => true,
		'regex' => "#(?:rutor\.info|rutor\.is)\/torrent/#", // .is, .info
		'dl_url' => 'http://d.rutor.info/download/',
		'target_element' => '<td class="header"',
		'redirect' => array(
			'from' => array('http://rutor.org/'),
			'to' => 'http://rutor.info/'
		)
	),
	'nnmclub' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#https://nnmclub.to/forum/viewtopic.php\?t=#",
		'login_url' => 'https://nnmclub.to/forum/login.php',
		'dl_url' => 'https://nnmclub.to/forum/download.php?id=',
		'login_input_name' => 'username',
		'password_input_name' => 'password',
		'target_element' => '<span class="seedmed">'
	),
	'rustorka' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#http://rustorka.com/forum/viewtopic.php\?t=#",
		'login_url' => 'http://rustorka.com/forum/login.php',
		'dl_url' => 'http://rustorka.com/forum/download.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<tr class="row3 tCenter">'
	),
	'booktracker' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "/https:\/\/booktracker.org\/viewtopic.php\?t=/",
		'login_url' => 'https://booktracker.org/login.php',
		'dl_url' => 'https://booktracker.org/download.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<div id="tor_info"'
	),
	'torrentwindows' => array(
		'enabled' => true,
		'regex' => "#torrent-wind.net/#",
		'dl_url' => 'https://torrent-wind.net/index.php?do=download&id=',
		'target_element' => '<div class="fdl-btn-size fx-col fx-center">'
	),
	'riperam' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#riperam.org/#",
		'login_url' => 'http://riperam.org/ucp.php?mode=login',
		'dl_url' => 'http://riperam.org/download/file.php?id=',
		'login_input_name' => 'username',
		'password_input_name' => 'password',
		'target_element' => '<td style="text-align: center; vertical-align: top;">'
	),
	'mptor' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#(?:megapeer\.ru|megapeer\.vip)\/torrent/#", // .ru, .vip
		'login_url' => 'http://megapeer.ru/takelogin.php',
		'dl_url' => 'http://megapeer.ru/download/',
		'login_input_name' => 'username',
		'password_input_name' => 'password',
		'target_element' => '<td class="heading"',
		'redirect' => array(
			'from' => array('http://megapeer.vip/'),
			'to' => 'http://megapeer.ru/'
		)
	),
	'tapochek' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "/https:\/\/tapochek.net\/viewtopic.php\?t=/",
		'login_url' => 'https://tapochek.net/login.php',
		'dl_url' => 'https://tapochek.net/download.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<p><img src="images/icon_dn.png"',
	),
	'uniongang' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#uniongang.club/torrent-#",
		'login_url' => 'http://uniongang.club/takelogin.php',
		'dl_url' => 'http://uniongang.club/download.php?id=',
		'login_input_name' => 'username',
		'password_input_name' => 'password',
		'target_element' => '<form method="post" action="takerate.php">',
	),
	'kinozal' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#kinozal.tv/details.php\?id=#",
		'login_url' => 'http://kinozal.tv/takelogin.php',
		'dl_url' => 'http://dl.kinozal.tv/download.php?id=',
		'login_input_name' => 'username',
		'password_input_name' => 'password',
		'target_element' => '<form id="cmt" method=post',
	),
	'kinozalguru' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#kinozal.guru/details.php\?id=#",
		'login_url' => 'https://kinozal.guru/takelogin.php',
		'dl_url' => 'http://dl.kinozal.guru/download.php?id=',
		'login_input_name' => 'username',
		'password_input_name' => 'password',
		'target_element' => '<form id="cmt" method=post',
	),
	'windowssoftinfo' => array(
		'enabled' => true,
		'regex' => "#windows-soft.info/#",
		'dl_url' => 'https://windows-soft.info/engine/download.php?id=',
		'target_element' => '<div class="fstory-rating">'
	),
	'ztorrents' => array(
		'enabled' => true,
		'regex' => "#z-torrents.ru/#",
		'dl_url' => '', // TODO
		'target_element' => '<div class="dle_b_appp"'
	),
	'piratbit' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#piratbit.org/topic/#",
		'login_url' => 'https://piratbit.org/login.php',
		'dl_url' => 'https://piratbit.org/dl.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<span class="fs11_bold thanked">',
	),
	'onlysoft' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "#https://only-soft.org/viewtopic.php\?t=#",
		'login_url' => 'https://only-soft.org/login.php',
		'dl_url' => 'https://only-soft.org/download.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<p class="small">',
	),
	'rutrackerru' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "/http:\/\/rutracker.ru\/viewtopic.php\?t=/",
		'login_url' => 'http://rutracker.ru/login.php',
		'dl_url' => 'http://rutracker.ru/dl.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<input type="radio" name=',
	),
	'ddgroupclub' => array(
		'enabled' => true,
		'auth' => true,
		'regex' => "/http:\/\/ddgroupclub.win\/viewtopic.php\?t=/",
		'login_url' => 'http://ddgroupclub.win/login.php',
		'dl_url' => 'http://ddgroupclub.win/dl.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<p class="small">',
	),
	'xxxtor' => array(
		'enabled' => true,
		'regex' => "#xxxtor.net#",
		'dl_url' => 'https://', // TODO
		'target_element' => '<div id="down">',
	)
);

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
	if (in_array($bb_cfg['torrent_parser']['parser_auth'], array('both', 'admin', 'moderator', 'user')) &&
		((!IS_AM && $bb_cfg['torrent_parser']['parser_auth'] === 'both') ||
			(!IS_ADMIN && $bb_cfg['torrent_parser']['parser_auth'] === 'admin') ||
			(!IS_MOD && $bb_cfg['torrent_parser']['parser_auth'] === 'moderator') ||
			(IS_GUEST && $bb_cfg['torrent_parser']['parser_auth'] === 'user'))) {
		bb_die($lang['NOT_AUTHORISED']);
	}
}

/**
 * Формирование главной страницы
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

	$template->assign_vars(array(
		'IN_PARSER' => true,
		'SUPPORTED_TRACKERS' => implode(', ', array_keys($trackers)),
		'SELECT_FORUM' => $cat_forum_select,
	));
} else {
	// Проверка ссылки
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		bb_die($lang['PARSER_INVALID_URL']);
	}
	$url = preg_replace('/^(https?:\/\/)(www\.)(.*)$/', '$1$3', $url);

	// Инициализация библиотеки для обращений
	$curl = new \Dinke\CurlHttpClient;
	$curl->setUserAgent(\Campo\UserAgent::random(array('agent_type' => 'Browser'))); // Случайный User-Agent
	// Настройка прокси
	// $curl->setProxy(''); // ip:port
	// $curl->setProxyAuth('login:pass'); // login:pass

	// Проверка вводимого URL адреса
	$tracker = null;
	$tracker_data = array();
	foreach ($trackers as $name => $data) {
		// Проверка на редиректы
		if (!empty($data['redirect']['from'])) {
			foreach ($data['redirect']['from'] as $fromUrl) {
				if (strpos($url, $fromUrl) === 0) {
					$url = str_replace($fromUrl, $data['redirect']['to'], $url);
					break;
				}
			}
		}
		// Проверка по регулярному выражению
		if (preg_match($data['regex'], $url)) {
			if (!$data['enabled']) {
				// Парсинг с трекера отключен
				bb_die(sprintf($lang['PARSER_TRACKER_DISABLED'], $name));
			}
			if ((isset($data['auth']) && $data['auth']) && (empty($bb_cfg['torrent_parser']['auth'][$name]['login']) || empty($bb_cfg['torrent_parser']['auth'][$name]['pass']))) {
				// Неверные данные авторизации
				bb_die(sprintf($lang['PARSER_EMPTY_AUTH'], $name));
			}
			$tracker = $name; // Название трекера
			$tracker_data = $data; // Настройки трекера
			break;
		}
	}
	if ($tracker === null || !is_array($tracker_data)) {
		die_and_refresh(sprintf($lang['PARSER_INVALID_TRACKER'], $url));
	}

	// ----------------------- Обращение к трекеру -----------------------
	// Подключение парсера
	require_once INC_DIR . "/parser/trackers/$tracker.php";

	// Авторизация
	if (isset($tracker_data['auth']) && $tracker_data['auth']) {
		if (empty($tracker_data['login_url'])) {
			bb_die($lang['PARSER_EMPTY_AUTH_LINK']);
		}

		$curl->storeCookies(COOKIES_PARS_DIR . '/' . $tracker . '_cookie.txt');
		$submit_vars = array(
			$tracker_data['login_input_name'] => $bb_cfg['torrent_parser']['auth'][$tracker]['login'],
			$tracker_data['password_input_name'] => $bb_cfg['torrent_parser']['auth'][$tracker]['pass'],
			'login' => true,
			'autologin' => 'on',
		);
		$curl->sendPostData($tracker_data['login_url'], $submit_vars);
	}

	// Получение содержимого
	$content = $curl->fetchUrl($url);
	$content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content));
	$pos = strpos($content, $tracker_data['target_element']);
	$content = substr($content, 0, $pos);

	// Проверка на пустую страницу
	if (empty($content)) {
		die_and_refresh($lang['PARSER_EMPTY_CONTENT']);
	}

	// Парсим HTML код страницы
	if ($message = $tracker($content)) {
		$id = $message['torrent']; // Идентификатор торрент-файла
		$subject = $message['title']; // Заголовок сообщения

		// Проверка идентификатора торрента
		if (empty($id) || !is_numeric($id)) {
			die_and_refresh(sprintf($lang['PARSER_CANT_GET_TORRENT'], $id));
		}

		// Проверка наличия заголовка
		if (empty($subject)) {
			die_and_refresh($lang['PARSER_CANT_GET_TITLE']);
		}

		// Получение торрент-файла
		$tracker_data['dl_url'] = isset($tracker_data['dl_url']) ? $tracker_data['dl_url'] : '';
		$torrent = $curl->fetchUrl($tracker_data['dl_url'] . $id);

		// Декодирование торрент-файла
		$tor = torrent_decode($torrent, $info_hash);

		// Проверка на повтор
		duplicate_check($info_hash, $subject, $url);

		// Прикрепляем торрент-файл
		attach_torrent_file($tor, $torrent, $hidden_form_fields);
	}
	// -------------------------------------------------------------------

	$hidden_form_fields .= '<input type="hidden" name="mode" value="newtopic" />';
	$hidden_form_fields .= '<input type="hidden" name="' . POST_FORUM_URL . '" value="' . $forum_id . '" />';

	generate_smilies('inline');

	// Формирование топика
	$template->assign_vars(array(
		'SUBJECT' => $subject,
		'MESSAGE' => $message['content'],
		'S_POST_ACTION' => "posting.php",

		'POSTING_SUBJECT' => true,
		'S_HIDDEN_FORM_FIELDS' => $hidden_form_fields,
	));
}

$template->assign_vars(array(
	'PAGE_TITLE' => $lang['PARSER_TITLE'],
	'PROGRESS_BAR_IMG' => $images['progress_bar'],
));

print_page('posting.tpl');
