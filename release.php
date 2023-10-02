<?php

set_time_limit(0);

//define('IN_FORUM', true);
define('BB_SCRIPT', 'release');
define('BB_ROOT', './');
require __DIR__ . '/common.php';
require INC_DIR . '/bbcode.php';
require INC_DIR . '/functions_autoparser.php';

//require CLASS_DIR. '/curl.php';

//TorrentPier\Legacy\Post();

$url = isset($_POST['url']) ? $_POST['url'] : '';
$url = str_replace('http://www.', 'http://', $url);
$hidden_form_fields = $message = $subject = '';

$forum_id = (int)request_var('forum_id', '');

// Start session management
$user->session_start(array('req_login' => true));

$attach_dir = get_attachments_dir();

if (!IS_AM && $bb_cfg['auth']['group_id']) {
	$vip = DB()->fetch_row("SELECT user_id FROM  " . BB_USER_GROUP . " WHERE group_id in({$bb_cfg['auth']['group_id']}) AND user_id = " . $userdata['user_id']);
	if (!$vip) bb_die('Извините, вы не состоите в соответствующей группе');
}
if (!$url) {
	// Get allowed for searching forums list
	if (!$forums = $datastore->get('cat_forums')) {
		$datastore->update('cat_forums');
		$forums = $datastore->get('cat_forums');
	}
	$cat_title_html = $forums['cat_title_html'];
	$forum_name_html = $forums['forum_name_html'];

	$excluded_forums_csv = $user->get_excluded_forums(AUTH_READ);
	$allowed_forums = array_diff(explode(',', $forums['tracker_forums']), explode(',', $excluded_forums_csv));

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
	$search_all_opt = '<option value="0">&nbsp;' . htmlCHR($lang['ALL_AVAILABLE']) . "</option>\n";
	$cat_forum_select = "\n<select class=\"form-control form-control-sm\" id=\"fs\" name=\"forum_id\" style=\"font-size: small;\">\n" . $search_all_opt . $opt . "</select>\n";

	$template->assign_vars(array(
		'URL' => true,
		'URL_DISPLAY' => 'nnmclub.to',
		'SELECT_FORUM' => $cat_forum_select,
	));
} else {
	$curl = new \Dinke\CurlHttpClient;

	if (preg_match("#https://nnmclub.to/forum/viewtopic.php\?t=#", $url)) {
		$tracker = 'nnmclub';

		if (!$bb_cfg['auth']['nnmclub']['login'] || !$bb_cfg['auth']['nnmclub']['pass']) {
			bb_die('not auth nnmclub');
		}
	} else {
		meta_refresh('release.php', '2');
		bb_die('not this tracker');
	}

	if ($tracker == 'nnmclub') {

		$curl->setUserAgent("Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1");
		$curl->storeCookies(COOKIES_PARS_DIR . '/nnm_cookie.txt');

		$submit_url = "https://nnmclub.to/forum/login.php";
		$submit_vars = array(
			'username' => $bb_cfg['auth']['nnmclub']['login'],
			'password' => $bb_cfg['auth']['nnmclub']['pass'],
			'login' => true,
		);

		$curl->sendPostData($submit_url, $submit_vars);

		$content = $curl->fetchUrl($url);
		$content = iconv('windows-1251', 'UTF-8', $content);
		$pos = strpos($content, '<span class="seedmed">');
		$content = substr($content, 0, $pos);

		if (!$content) {
			meta_refresh('release.php', '2');
			bb_die('Занято ;) - Приходите через 20 минут.');
		}

		if ($message = nnmclub($content)) {
			$tor = nnmclub($content, 'torrent');
			$id = $tor[2];
			$name = $tor[1];
			$name = str_replace('[NNM-Club.info] ', '', $name);
			$name = str_replace('[NNMClub.to]_', '', $name);
			$name = str_replace('[NNM-Club.me]_', '', $name);
			$name = str_replace('[NNM-Club.ru]_', '', $name);
			$name = str_replace('[RG Games]', '', $name);
			$name = str_replace('[R.G. Revenants]', '', $name);
			$name = str_replace('[R.G. Mechanics]', '', $name);

			if (!$id) {
				meta_refresh('release.php', '2');
				bb_die('Торрент не найден');
			}

			$torrent = $curl->fetchUrl("https://nnmclub.to/forum/download.php?id=$id");
			if (class_exists('\SandFox\Bencode\Bencode')) {
				$tor = \SandFox\Bencode\Bencode::decode($torrent);
				$info_hash = pack('H*', sha1(\SandFox\Bencode\Bencode::encode($tor['info'])));
			} else if (class_exists('\Arokettu\Bencode\Bencode')) {
				$tor = \Arokettu\Bencode\Bencode::decode($torrent);
				$info_hash = pack('H*', sha1(\Arokettu\Bencode\Bencode::encode($tor['info'])));
			} else {
				bb_die('Отсутствует библиотека для бинкодирования торрента');
			}
			$info_hash_sql = rtrim(DB()->escape($info_hash), ' ');
			$info_hash_md5 = md5($info_hash);

			if ($row = DB()->fetch_row("SELECT topic_id FROM " . BB_BT_TORRENTS . " WHERE info_hash = '$info_hash_sql' LIMIT 1")) {
				$title = nnmclub($content, 'title');
				bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
			}

			if (count($tor)) {
				$new_name = md5($torrent);
				$file = fopen("$attach_dir/$new_name.torrent", 'w');
				fputs($file, $torrent);
				fclose($file);

				$hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
				$hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
				$hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
				$hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', false) . '._[soft-torrent.ru].torrent" />';
				$hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
				$hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
				$hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
				$hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
				$hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
				$hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
			}
		}

		$subject = nnmclub($content, 'title');
	}

	$hidden_form_fields .= '<input type="hidden" name="mode" value="newtopic" />';
	$hidden_form_fields .= '<input type="hidden" name="' . POST_FORUM_URL . '" value="' . $forum_id . '" />';

	generate_smilies('inline');

	$template->assign_vars(array(
		'SUBJECT' => $subject,
		'MESSAGE' => $message,
		'S_POST_ACTION' => "posting.php",

		'POSTING_SUBJECT' => true,
		'S_HIDDEN_FORM_FIELDS' => $hidden_form_fields,
	));
}

$template->assign_vars(array(
	'PAGE_TITLE' => 'Grabber Trackers',
));

print_page('posting.tpl');
