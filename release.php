<?php

set_time_limit(0);

//define('IN_FORUM', true);
define('BB_SCRIPT', 'release');
define('BB_ROOT', './');
require __DIR__ . '/common.php';
require INC_DIR . '/bbcode.php';
require INC_DIR . '/functions_autoparser.php';

$url = isset($_POST['url']) ? $_POST['url'] : '';
$url = str_replace('http://www.', 'http://', $url);
$hidden_form_fields = $message = $subject = '';

$forum_id = (int)request_var('forum_id', '');

// Start session management
$user->session_start(array('req_login' => true));
warning(5);
$attach_dir = get_attachments_dir();

function decodeEmailProtection($encodedString): string
{
    // Holds the final output
    $decodedString = '';

    // Extract the first 2 letters
    $keyInHex = substr($encodedString, 0, 2);

    // Convert the hex-encoded key into decimal
    $key = intval($keyInHex, 16);

    // Loop through the remaining encoded characters in steps of 2
    for ($n = 2; $n < strlen($encodedString); $n += 2) {
        // Get the next pair of characters
        $charInHex = substr($encodedString, $n, 2);

        // Convert hex to decimal
        $char = intval($charInHex, 16);

        // XOR the character with the key to get the original character
        $output = $char ^ $key;

        // Append the decoded character to the output
        $decodedString .= chr($output);
    }
    return mb_convert_encoding($decodedString, 'UTF-8');
}

function rgb2html($r, $g = -1, $b = -1)
{
    if (is_array($r) && sizeof($r) == 3)
        list($r, $g, $b) = $r;

    $r = intval($r);
    $g = intval($g);
    $b = intval($b);

    $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
    $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
    $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));

    $color = (strlen($r) < 2 ? '0' : '') . $r;
    $color .= (strlen($g) < 2 ? '0' : '') . $g;
    $color .= (strlen($b) < 2 ? '0' : '') . $b;
    return '#' . $color;
}

function closetags($tagstext)
{
// Выбираем абсолютно все теги
    if (preg_match_all("/<([/]?)([wd]+)[^>/]*>/", $tagstext, $matches, PREG_SET_ORDER)) {
        $stack = array();
        foreach ($matches as $k => $match) {
            $tag = strtolower($match[2]);
            if (!$match[1])
                // если тег открывается добавляем в стек
                $stack[] = $tag;
            elseif (end($stack) == $tag)
                // если тег закрывается, удаляем из стека
                array_pop($stack);
            else
                // если это закрывающий тег, который не открыт, открываем
                $tagstext = '<' . $tag . '>' . $tagstext;
        }
        while ($tag = array_pop($stack))
            // закрываем все открытые теги
            $tagstext .= '</' . $tag . '>';
    }
    return $tagstext;
}

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

    if (!$allowed_forums) {
        bb_die('Нету форумов на которых разрешена регистрация торрентов');
    }

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
        'URL_DISPLAY' => 'tapochek.net, rutor.info / rutor.is, booktracker.org, megapeer.ru / megapeer.vip, riperam.org, torrent-wind.net, only-soft.org, z-torrents.ru, uniongang.club, <br> rustorka.com, nnmclub.to, rutracker.org, rutracker.ru, kinozal.tv / kinozal.guru, piratbit.org,  ddgroupclub.win, xxxtor.net',
        'SELECT_FORUM' => $cat_forum_select,
    ));
} else {
    $curl = new \Dinke\CurlHttpClient;

    if (preg_match("/https:\/\/rutracker.org\/forum\/viewtopic.php\?t=/", $url)) {
        $tracker = 'rutracker';
        if (!$bb_cfg['auth']['rutracker']['login'] || !$bb_cfg['auth']['rutracker']['pass']) {
            bb_die('not auth rutracker');
        }
    } elseif (preg_match("#rutor.info/torrent/#", $url)) {
        $tracker = 'rutor';
    } elseif (preg_match("#rutor.is/torrent/#", $url)) {
        $tracker = 'rutor';
    } elseif (preg_match("#https://nnmclub.to/forum/viewtopic.php\?t=#", $url)) {
        $tracker = 'nnmclub';

        if (!$bb_cfg['auth']['nnmclub']['login'] || !$bb_cfg['auth']['nnmclub']['pass']) {
            bb_die('not auth nnmclub');
        }
    } elseif (preg_match("#http://rustorka.com/forum/viewtopic.php\?t=#", $url)) {
        $tracker = 'rustorka';
        if (!$bb_cfg['auth']['rustorka']['login'] || !$bb_cfg['auth']['rustorka']['pass']) {
            bb_die('not auth rustorka');
        }
    } elseif (preg_match("/https:\/\/booktracker.org\/viewtopic.php\?t=/", $url)) {
        $tracker = 'booktracker';
        if (!$bb_cfg['auth']['booktracker']['login'] || !$bb_cfg['auth']['booktracker']['pass']) {
            bb_die('not auth booktracker');
        }
    } elseif (preg_match("#torrent-wind.net/#", $url)) {
        $tracker = 'torrentwindows';
    } elseif (preg_match("#riperam.org/#", $url)) {
        $tracker = 'riperam';
        if (!$bb_cfg['auth']['riperam']['login'] || !$bb_cfg['auth']['riperam']['pass']) {
            bb_die('not auth riperam');
        }
    } elseif (preg_match("#megapeer.ru/torrent/#", $url)) {
        $tracker = 'mptor';
        if (!$bb_cfg['auth']['mptor']['login'] || !$bb_cfg['auth']['mptor']['pass']) {
            bb_die('not auth mptor');
        }
    } elseif (preg_match("#megapeer.vip/torrent/#", $url)) {
        $tracker = 'mptor';
        if (!$bb_cfg['auth']['mptor']['login'] || !$bb_cfg['auth']['mptor']['pass']) {
            bb_die('not auth mptor');
        }
    } elseif (preg_match("/https:\/\/tapochek.net\/viewtopic.php\?t=/", $url)) {
        $tracker = 'tapochek';
        if (!$bb_cfg['auth']['tapochek']['login'] || !$bb_cfg['auth']['tapochek']['pass']) {
            bb_die('not auth tapochek');
        }
    } elseif (preg_match("#uniongang.club/torrent-#", $url)) {
        $tracker = 'uniongang';
        if (!$bb_cfg['auth']['uniongang']['login'] || !$bb_cfg['auth']['uniongang']['pass']) {
            bb_die('not auth uniongang');
        }
    } elseif (preg_match("#kinozal.tv/details.php\?id=#", $url)) {
        $tracker = 'kinozal';
        if (!$bb_cfg['auth']['kinozal']['login'] || !$bb_cfg['auth']['kinozal']['pass']) {
            bb_die('not auth kinozal');
        }
    } elseif (preg_match("#kinozal.guru/details.php\?id=#", $url)) {
        $tracker = 'kinozalguru';
        if (!$bb_cfg['auth']['kinozal']['login'] || !$bb_cfg['auth']['kinozal']['pass']) {
            bb_die('not auth kinozal');
        }
    } elseif (preg_match("#windows-soft.info/#", $url)) {
        $tracker = 'windowssoftinfo';
    } elseif (preg_match("#z-torrents.ru/#", $url)) {
        $tracker = 'ztorrents';
    } elseif (preg_match("#piratbit.org/topic/#", $url)) {
        $tracker = 'piratbit';
        if (!$bb_cfg['auth']['piratbit']['login'] || !$bb_cfg['auth']['piratbit']['pass']) {
            bb_die('not auth piratbit');
        }
    } elseif (preg_match("#https://only-soft.org/viewtopic.php\?t=#", $url)) {
        $tracker = 'onlysoft';
        if (!$bb_cfg['auth']['onlysoft']['login'] || !$bb_cfg['auth']['onlysoft']['pass']) {
            bb_die('not auth only-soft');
        }
    } elseif (preg_match("/http:\/\/rutracker.ru\/viewtopic.php\?t=/", $url)) {
        $tracker = 'rutrackerru';
        if (!$bb_cfg['auth']['rutrackerru']['login'] || !$bb_cfg['auth']['rutrackerru']['pass']) {
            bb_die('not auth rutrackerru');
        }
    } elseif (preg_match("/http:\/\/ddgroupclub.win\/viewtopic.php\?t=/", $url)) {
        $tracker = 'ddgroupclub';
        if (!$bb_cfg['auth']['ddgroupclub']['login'] || !$bb_cfg['auth']['ddgroupclub']['pass']) {
            bb_die('not auth ddgroupclub');
    } elseif (preg_match("#xxxtor.net#", $url)){
	    $tracker = 'xxxtor';
		}
    } else {
        meta_refresh('release.php', '2');
        bb_die('not this tracker');
    }

    if ($tracker == 'rutracker') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/rutracker_cookie.txt');

        $submit_url = "https://rutracker.org/forum/login.php";
        $submit_vars = array(
            'login_username' => $bb_cfg['auth']['rutracker']['login'],
            'login_password' => $bb_cfg['auth']['rutracker']['pass'],
            'login' => true,
        );
        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $content = iconv('windows-1251', 'UTF-8', $content);
//var_dump($content);
        $pos = strpos($content, '<div style="padding-top: 6px;">');
        $content = substr($content, 0, $pos);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = rutracker($content)) {
            $id = rutracker($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("https://rutracker.org/forum/dl.php?t=$id");

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
                $title = rutracker($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }
            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = rutracker($content, 'title');
    } elseif ($tracker == 'rutor') {
        if (preg_match("#http://rutor.org/#", $url)) {
            $new_host = 'rutor.info';
            $url = str_replace("http://rutor.org/", "http://$new_host/", $url);
        }


        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");

        $content = $curl->fetchUrl($url);
        $pos = strpos($content, '<td class="header"');
        //var_dump($content);

        $content = substr($content, 0, $pos);
        //var_dump($content);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('false content');
        }

        if ($message = rutor($content)) {
            $id = rutor($content, 'torrent');
            //dump($id);

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://d.rutor.info/download/$id");
            //var_dump($torrent);
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
                $title = rutor($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = make_rand_str(6) . '_' . md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);


                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
            }
        }
        $subject = rutor($content, 'title');
    } elseif ($tracker == 'nnmclub') {

        //use proxy
        $curl->setProxy('38.170.252.172:9527');
        //use proxy auth
        $curl->setProxyAuth('cZbZMH:6qFmYC');
        $curl->setUserAgent("Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1");
        $curl->storeCookies(COOKIES_PARS_DIR . '/nnm_cookie.txt');

        $submit_url = "https://nnmclub.to/forum/login.php";
        //$snoopy->_submit_method = "POST";
        $submit_vars = array(
            'username' => $bb_cfg['auth']['nnmclub']['login'],
            'password' => $bb_cfg['auth']['nnmclub']['pass'],
            'login' => true,
        );
        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);

        //$content = openUrlCloudflare($url);

        $content = iconv('windows-1251', 'UTF-8', $content);

        $pos = strpos($content, '<span class="seedmed">');

        $content = substr($content, 0, $pos);
        // dump($content);

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
//dump($id);

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

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
            }
        }
        $subject = nnmclub($content, 'title');
    } elseif ($tracker == 'rustorka') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/rustorka_cookie.txt');

        $submit_url = "http://rustorka.com/forum/login.php";
        $submit_vars = array(
            //"cookie_test"		=> "$cookie_id",
            "login_username" => $bb_cfg['auth']['rustorka']['login'],
            "login_password" => $bb_cfg['auth']['rustorka']['pass'],
            "autologin" => "on",
            "login" => true,
        );

        //dump($submit_vars);

        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $content = iconv('windows-1251', 'UTF-8', $content);
        $pos = strpos($content, '<tr class="row3 tCenter">');
        $content = substr($content, 0, $pos);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('попробуйте еще раз,не прошла авторизация');
        }

        if ($message = rustorka($content)) {

            $tor = rustorka($content, 'torrent');
            $id = $tor[2];
            $name = $tor[1];

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://rustorka.com/forum/download.php?id=$id");
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
                $title = rustorka($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
            }
        }
        $subject = rustorka($content, 'title');
    } elseif ($tracker == 'booktracker') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/booktracker_cookie.txt');

        $submit_url = "https://booktracker.org/login.php";
        $submit_vars = array(
            'login_username' => $bb_cfg['auth']['booktracker']['login'],
            'login_password' => $bb_cfg['auth']['booktracker']['pass'],
            'login' => true,
        );
        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $pos = strpos($content, '<div id="tor_info"');
        $content = substr($content, 0, $pos);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = booktracker($content)) {
            $id = booktracker($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("https://booktracker.org/download.php?id=$id");
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
                $title = booktracker($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }
            if (is_array($tor) && count($tor)) {
                $new_name = make_rand_str(6) . '_' . md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
            }
        }
        $subject = booktracker($content, 'title');
    } elseif ($tracker == 'torrentwindows') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $content = $curl->fetchUrl($url);


        $pos = strpos($content, '<div class="fdl-btn-size fx-col fx-center">');
        $content = substr($content, 0, $pos);
        //var_dump($content);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('false content');
        }

        if ($message = torrentwindows($content)) {


            $id = torrentwindows($content, 'torrent');
//dump($id);

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("https://torrent-wind.net/index.php?do=download&id=$id");

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
                $title = torrentwindows($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = make_rand_str(6) . '_' . md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);


                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
            }
        }
        $subject = torrentwindows($content, 'title');
    } elseif ($tracker == 'riperam') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/riperam_cookie.txt');

        $submit_url = "http://riperam.org/ucp.php?mode=login";
        $submit_vars = array(
            'username' => $bb_cfg['auth']['riperam']['login'],
            'password' => $bb_cfg['auth']['riperam']['pass'],
            'autologin' => 'on',
            'viewonline' => 'on',
            'login' => true,
        );

        $curl->sendPostData($submit_url, $submit_vars);
        $curl->setReferer($submit_url);
        $content = $curl->fetchUrl($url);

//var_dump($content);
        $pos = strpos($text, '<div class="content"');
        $text = substr($text, $pos);
        $pos = strpos($content, '<td style="text-align: center; vertical-align: top;">');
        $content = substr($content, 0, $pos);


        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = riperam($content)) {
            $id = riperam($content, 'torrent');
            //var_dump($id);

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://riperam.org/download/file.php?id=$id");
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
                $title = riperam($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = riperam($content, 'title');
    } elseif ($tracker == 'mptor') {
        if (preg_match("#http://megapeer.vip/#", $url)) {
            $new_host = 'megapeer.ru';
            $url = str_replace("http://megapeer.vip/", "http://$new_host/", $url);
        }

        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/mptor_cookie.txt');

        $submit_url = "http://megapeer.ru/takelogin.php";
        $submit_vars = array(
            'username' => $bb_cfg['auth']['mptor']['login'],
            'password' => $bb_cfg['auth']['mptor']['pass'],
            'login' => true,
        );
        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $content = iconv('windows-1251', 'UTF-8', $content);
        $pos = strpos($content, '<td class="heading"');
        //var_dump($content);
        $content = substr($content, 0, $pos);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = mptor($content)) {

            $id = mptor($content, 'torrent');
            //var_dump($id);

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://megapeer.ru/download/$id");
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
                $title = mptor($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = mptor($content, 'title');

    } elseif ($tracker == 'tapochek') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/tapochek_cookie.txt');

        $submit_url = "https://tapochek.net/login.php";
        $submit_vars = array(
            'login_username' => $bb_cfg['auth']['tapochek']['login'],
            'login_password' => $bb_cfg['auth']['tapochek']['pass'],
            'login' => true,
        );

        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $content = iconv('windows-1251', 'UTF-8', $content);

        $pos = strpos($content, '<p><img src="images/icon_dn.png"');
        $content = substr($content, 0, $pos);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = tapochek($content)) {

            $id = tapochek($content, 'torrent');
//var_dump($id);

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("https://tapochek.net/download.php?id=$id");
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
                $title = tapochek($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
            }
        }
        $subject = tapochek($content, 'title');
    } elseif ($tracker == 'uniongang') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->setReferer($url);
        $curl->storeCookies(COOKIES_PARS_DIR . '/uniongang_cookie.txt');

        $submit_url = "http://uniongang.club/takelogin.php";
        $submit_vars = array(
            'username' => $bb_cfg['auth']['uniongang']['login'],
            'password' => $bb_cfg['auth']['uniongang']['pass'],
        );

        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $content = iconv('windows-1251', 'UTF-8', $content);
//var_dump($content);
        $content = preg_replace('/([\r\n])[\s]+/is', "\\1", $content);
        $pos = strpos($text, '<table width="100%" border="1" cellspacing="0" cellpadding="5">');
        $text = substr($text, $pos);
        $pos = strpos($content, '<form method="post" action="takerate.php">');
        $content = substr($content, 0, $pos);


        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = uniongang($content)) {
            $id = uniongang($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://uniongang.club/download.php?id=$id");
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
                $title = uniongang($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }
            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = uniongang($content, 'title');
    } elseif ($tracker == 'kinozal') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        //$curl->setReferer($url);
        $curl->storeCookies(COOKIES_PARS_DIR . '/kinozal_cookie.txt');

        $submit_url = "http://kinozal.tv/takelogin.php";
        $submit_vars = array(
            'username' => $bb_cfg['auth']['kinozal']['login'],
            'password' => $bb_cfg['auth']['kinozal']['pass'],
            'login' => true,
        );

        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $content = iconv('windows-1251', 'UTF-8', $content);
        //dump($content);
//var_dump($content);
        $pos = strpos($content, '<form id="cmt" method=post');
        $content = substr($content, 0, $pos);


        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = kinozal($content)) {

            $id = kinozal($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://dl.kinozal.tv/download.php?id=$id");
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
                $title = kinozal($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }


            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = kinozal($content, 'title');
    } elseif ($tracker == 'kinozalguru') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        //$curl->setReferer($url);
        $curl->storeCookies(COOKIES_PARS_DIR . '/kinozalguru_cookie.txt');

        $submit_url = "https://kinozal.guru/takelogin.php";
        $submit_vars = array(
            'username' => $bb_cfg['auth']['kinozal']['login'],
            'password' => $bb_cfg['auth']['kinozal']['pass'],
            'login' => true,
        );

        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $content = iconv('windows-1251', 'UTF-8', $content);
        //dump($content);
//var_dump($content);
        $pos = strpos($content, '<form id="cmt" method=post');
        $content = substr($content, 0, $pos);


        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = kinozalguru($content)) {

            $id = kinozalguru($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://dl.kinozal.guru/download.php?id=$id");
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
                $title = kinozalguru($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }


            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = kinozalguru($content, 'title');
    } elseif ($tracker == 'windowssoftinfo') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");

        $content = $curl->fetchUrl($url);

        $pos = strpos($content, '<div class="fstory-rating">');
        $content = substr($content, 0, $pos);
        //var_dump($content);


        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = windowssoftinfo($content)) {

            $id = windowssoftinfo($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("https://windows-soft.info/engine/download.php?id=$id");
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
                $title = windowssoftinfo($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = windowssoftinfo($content, 'title');
    } elseif ($tracker == 'ztorrents') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $content = $curl->fetchUrl($url);

        $pos = strpos($content, '<div class="dle_b_appp"');
        $content = substr($content, 0, $pos);
        //var_dump($content);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('false content');
        }

        if ($message = ztorrents($content)) {

            $id = ztorrents($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("$id");

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
                $title = ztorrents($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = make_rand_str(6) . '_' . md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);


                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
            }
        }
        $subject = ztorrents($content, 'title');
    } elseif ($tracker == 'piratbit') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/piratbit_cookie.txt');

        $submit_url = "https://piratbit.org/login.php";
        $submit_vars = array(
            'login_username' => $bb_cfg['auth']['piratbit']['login'],
            'login_password' => $bb_cfg['auth']['piratbit']['pass'],
            'login' => true,
        );
        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        //$content  = iconv('windows-1251', 'UTF-8', $content);

        $pos = strpos($content, '<span class="fs11_bold thanked">');
        $content = substr($content, 0, $pos);
//var_dump($content);
        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = piratbit($content)) {

            $id = piratbit($content, 'torrent');
            //dump($id);

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("https://piratbit.org/dl.php?id=$id");
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
                $title = piratbit($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = piratbit($content, 'title');
    } elseif ($tracker == 'onlysoft') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/onlysoft_cookie.txt');

        $submit_url = "https://only-soft.org/login.php";
        $submit_vars = array(
            'login_username' => $bb_cfg['auth']['onlysoft']['login'],
            'login_password' => $bb_cfg['auth']['onlysoft']['pass'],
            'autologin' => 'on',
            'login' => true,
        );
        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
        $pos = strpos($content, '<p class="small">');
        $content = substr($content, 0, $pos);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('false content');
        }

        if ($message = onlysoft($content)) {
            $id = onlysoft($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("https://only-soft.org/download.php?id=$id");
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
                $title = onlysoft($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }
            if (is_array($tor) && count($tor)) {
                $new_name = make_rand_str(6) . '_' . md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
            }
        }
        $subject = onlysoft($content, 'title');
    } elseif ($tracker == 'rutrackerru') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/rutrackerru_cookie.txt');

        $submit_url = "http://rutracker.ru/login.php";
        $submit_vars = array(
            'login_username' => $bb_cfg['auth']['rutrackerru']['login'],
            'login_password' => $bb_cfg['auth']['rutrackerru']['pass'],
            "autologin" => "on",
            'login' => true,
        );
        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
//var_dump($content);
        $pos = strpos($content, '<input type="radio" name=');
        $content = substr($content, 0, $pos);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = rutrackerru($content)) {
            $id = rutrackerru($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://rutracker.ru/dl.php?id=$id");

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
                $title = rutrackerru($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = rutrackerru($content, 'title');
    } elseif ($tracker == 'ddgroupclub') {
        $curl->setUserAgent("Mozilla/6.0.2 (compatible; MSIE 6.0; Windows NT 5.1)");
        $curl->storeCookies(COOKIES_PARS_DIR . '/ddgroupclub_cookie.txt');

        $submit_url = "http://ddgroupclub.win/login.php";
        $submit_vars = array(
            'login_username' => $bb_cfg['auth']['ddgroupclub']['login'],
            'login_password' => $bb_cfg['auth']['ddgroupclub']['pass'],
            "autologin" => "on",
            'login' => true,
        );
        $curl->sendPostData($submit_url, $submit_vars);

        $content = $curl->fetchUrl($url);
//var_dump($content);
        $pos = strpos($content, '<p class="small">');
        $content = substr($content, 0, $pos);

        if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

        if ($message = ddgroupclub($content)) {
            $id = ddgroupclub($content, 'torrent');

            if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }

            $torrent = $curl->fetchUrl("http://ddgroupclub.win/dl.php?id=$id");

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
                $title = ddgroupclub($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }

            if (is_array($tor) && count($tor)) {
                $new_name = md5($torrent);
                $file = fopen("$attach_dir/$new_name.torrent", 'w');
                fputs($file, $torrent);
                fclose($file);

                $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';

            }
        }
        $subject = ddgroupclub($content, 'title');
    }elseif ($tracker == 'xxxtor')
    {
		$content  = $curl->fetchUrl($url);

	    $content = $curl->fetchUrl($url);
	    $pos = strpos($content, '<div id="down">');
		$content = substr($content, 0, $pos);
		
		if (!$content) {
            meta_refresh('release.php', '2');
            bb_die('Занято ;) - Приходите через 20 минут.');
        }

	    if ($message = xxxtor($content))
	    {
		    $id = xxxtor($content, 'torrent');
			
			if (!$id) {
                meta_refresh('release.php', '2');
                bb_die('Торрент не найден');
            }
			
			$torrent = $curl->fetchUrl("https://$id");
		    			
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
                $title = ddgroupclub($content, 'title');
                bb_die('Повтор. <a target="_blank" href="' . $url . '">' . $title . '</a> - <a href="./viewtopic.php?t=' . $row['topic_id'] . '">' . $title . '</a>');
            }
			if (count($tor))
		    {
			    $new_name = make_rand_str(6) .'_'. md5($torrent);
			    $file = fopen("$attach_dir/$new_name.torrent", 'w');
				fputs($file, $torrent);
		        fclose($file);

			    $hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
                $hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . $attach_dir . '/' . $new_name . '.torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . bb_date(TIMENOW, 'd-m-Y H:i', 'false') . '._[' . $bb_cfg['sitename'] . '].torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
                $hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="application/x-bittorrent" />';
                $hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize("$attach_dir/$new_name.torrent") . '" />';
                $hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="" />';
                $hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
	        }
	    }
	    $subject = xxxtor($content, 'title');
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

print_page('posting2.tpl');
