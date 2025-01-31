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

if (!defined('BB_ROOT')) {
	die(basename(__FILE__));
}

/**
 * Парсер с kinozal.tv
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function kinozal($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match("#<a href=\".*?\" class=\"r\d+\">([\s\S]*?)</a>#", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a href=\"[^<]*?download.php\?id=(\d+)\" title=\".*?\"><img src=\"/pic/dwn_torrent.gif\".*?</a>#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	$match_screen = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Скриншоты<\/a>/", $text, $source, PREG_SET_ORDER);
	$url_id = @$source[0][1];
	$pagesd = @$source[0][2];
	if ($match_screen) {
		$screen = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_id&pagesd=$pagesd");
		$screenschot = "\n[spoiler=\"Скриншоты\"][align=center]" . $screen . "[/align][/spoiler]";
	} else {
		$screenschot = false;
	}

	$match_screen2 = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Качество<\/a>/", $text, $source, PREG_SET_ORDER);
	$url_id2 = @$source[0][1];
	$pagesd2 = @$source[0][2];
	if ($match_screen2) {
		$screen2 = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_id2&pagesd=$pagesd2");
		$screenschot2 = "\n[spoiler=\"Качество\"][align=center]" . $screen2 . "[/align][/spoiler]";
	} else {
		$screenschot2 = false;
	}

	$match_tr = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Треклист<\/a>/", $text, $source, PREG_SET_ORDER);
	$url_idtr = @$source[0][1];
	$pagesdtr = @$source[0][2];
	if ($match_tr) {
		$tr = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_idtr&pagesd=$pagesdtr");
		$track = "\n[spoiler=\"Треклист\"]\n[list]\n" . $tr . "\n[/list]\n[/spoiler]";
	} else {
		$track = false;
	}

	$match_movie_content = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Содержание<\/a>/", $text, $source, PREG_SET_ORDER);
	$url_idm = @$source[0][1];
	$pagesdm = @$source[0][2];
	if ($match_movie_content) {
		$movie_content = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_idm&pagesd=$pagesdm");
		$movie = "\n[spoiler=\"Содержание\"]\n" . $movie_content . "\n[/spoiler]";
	} else {
		$movie = false;
	}

	$match_cover = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Обложки<\/a>/", $text, $source, PREG_SET_ORDER);
	$url_idcv = @$source[0][1];
	$pagesdcv = @$source[0][2];
	if ($match_cover) {
		$content_cover = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_idcv&pagesd=$pagesdcv");
		$cover = "\n[spoiler=\"Обложки\"]\n[align=center]" . $content_cover . "[/center]\n[/spoiler]";
	} else {
		$cover = false;
	}

	$text = preg_replace('#kinopoisk.ru\/film\/(\d+)\/#', "/film/$1", $text);
	$text = preg_replace('#/i/poster/#', "http://kinozal.tv/i/poster/$1", $text);

	preg_match_all('/<li class="img"><a href="\/details.*?".*?><img src="([^<]*?)" .*?><\/a><\/li>/', $text, $pic, PREG_SET_ORDER);
	$poster = ($pic[0][1]) ? "[case]" . $pic[0][1] . "[/case]\n" : "";

	preg_match_all("#class=\"cat_img_r\"([\s\S]*?)<div class=\"bx2_0\">#si", $text, $source, PREG_SET_ORDER);

	preg_match_all('/<a href="http[^<]*?imdb.com\/title\/(\w+\d+)\/" target="_blank">IMDb.*?<\/a>/', $text, $imdb, PREG_SET_ORDER);
	$imdb_rating = ($imdb[0][1]) ? "[imdb]https://www.imdb.com/title/" . $imdb[0][1] . "[/imdb]" : "";

	preg_match_all('/<a href="http[^<]*?kinopoisk.ru\/film\/(\d+)" target="_blank">Кинопоиск.*?<\/a>/', $text, $kp, PREG_SET_ORDER);

	$kp_rating = isset($kp[0][1]) ? "[kp]https://www.kinopoisk.ru/film/" . $kp[0][1] . "[/kp]" : "";
	$text = $poster . $source[0][1] . $imdb_rating . "&nbsp;" . $kp_rating . $track . $movie . $screenschot . $cover . $screenschot2;

	$text = preg_replace('/<div class="bx1 justify">([\s\S]*?)<\/div>/', "\n[hr]\\1", $text);

	$text = preg_replace('/<script.*?script>/', '', $text);

	$text = preg_replace('/<td.*?>.*?<\/td>/', '', $text);
	$text = preg_replace('/<li.*?>.*?<\/li>/', "\n[hr]", $text);
	$text = preg_replace("/\n\[hr\]\n\[hr\]\n\[hr\]\n\[hr\]/", "\n[hr]\n[hr]\n", $text);
	$text = preg_replace('/<a href=".*?" class="sba" target="_blank">([\s\S]*?)<\/a>/', '[color=blue]\\1[/color]', $text);
	$text = str_replace('<div class="clearer"></div>', '', $text);
	$text = str_replace('<br />', "", $text);
	$text = str_replace('Подобные раздачи не найдены', "", $text);

	$text = str_replace('<center>', "[align=center]", $text);
	$text = str_replace('</center>', "[/align]", $text);

	$text = preg_replace('/<img src="([^<]*?)" alt="">/', "[img]\\1[/img]", $text);
	$text = preg_replace('/<img border="0" src=".*?">/', '', $text);

	$text = str_replace('&#039;', "'", $text);
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace('&gt;', '>', $text);
	$text = str_replace('&lt;', '<', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<b>([^<]*?)<(?=\/)\/b>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<u>([^<]*?)<(?=\/)\/u>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<i>([^<]*?)<(?=\/)\/i>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<s>([^<]*?)<(?=\/)\/s>/', '[s]$1[/s]', $text);
		$text = preg_replace('/<a href="([^<]*?)" class="gPic" rel="galI".*?><img.*?><\/a>/', "[thumb]$1[/thumb]", $text);
		$text = preg_replace('/<font style="font-size: ([^<]*?)pt">([^<]*?)<(?=\/)\/font>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
		$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\\2[/align]", $text);
		$text = preg_replace('/<span style="color: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);

		$text = preg_replace('/<a href="([^<]*?)" target="_blank">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<div class="spoiler-wrap"><div class="spoiler-head folded clickable">([^<]*?)<\/div><div class="spoiler-body">([^<]*?)<(?=\/)\/div><(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
	}

	$text = preg_replace('#\[url=[^<]*?kinozal.*?\](.*?)\[/url\]#', "\\1", $text);
	$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
	$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);

	// Вставка плеера
	insert_video_player($text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
