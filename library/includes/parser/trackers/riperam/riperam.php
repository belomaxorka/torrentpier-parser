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
 * Парсер с riperam.org
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function riperam($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match('#<div class="h1cla"><h1>([\s\S]*?)</h1></div>#', $text, $matches);
	$title = $matches[1];
	$title = str_replace(' - Скачать торрент бесплатно', '', $title);

	// ------------------- Get download link -------------------
	preg_match('#<a href=\"./download/file.php\?id=(.*?)\"><b>#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	preg_match_all('/<div class=\"content"\>([\s\S]*?)<\/td><td style="vertical-align: top; padding-top: 30px;">/', $text, $source, PREG_SET_ORDER);

	preg_match_all('/<a href="(.*?)" rel="prettyPhotoPosters.*?"><img src=".*?".*?><\/a>/', $text, $pic, PREG_SET_ORDER);
	$poster = ($pic[0][1]) ? "[img=right]" . $pic[0][1] . "[/img]\n\n" : "";
	$text = $poster . @$source[0][1];

	$text = preg_replace('/<div class="content">/', '', $text);
	$text = str_replace('<hr/>', "\n[hr]\n", $text);

	$text = str_replace('./go.html?', '', $text);
	$text = str_replace('?ref_=tt_mv_close', '', $text);
	$text = str_replace('<br/>', "\n", $text);
	$text = preg_replace('#</td><td style="vertical-align: top; padding-top: 30px;">[\s\S]*?<table[\s\S]*?><tr><td>#', "", $text);
	$text = str_replace('<div class="clear"></div>', "", $text);

	$text = preg_replace('/<var title="(.*?)" class="postImg".*?><\/var>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<img width=".*?" src="(.*?)" alt="">/', '[img]$1[/img]', $text);
	$text = preg_replace('/<img width=".*?" src="(.*?)" alt>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<a href="(.*?)" rel="prettyPhotoSscreen.*?">.*?<\/a>/', '[thumb]$1[/thumb]', $text);

	$text = preg_replace('/<iframe.*?src="([^<]*?)" frameborder.*?allowfullscreen><\/iframe>/', '[youtube]$1[/youtube]', $text);
	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<span style="font-style: italic">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<span style="font-weight: bold">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<span style="text-decoration: underline">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<div style="text-align: ([\s\S]*?);">([\s\S]*?)<(?=\/)\/div>/', '[align=$1]$2[/align]', $text);
		$text = preg_replace('/<span style="color: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<span style="font-size: ([^<]*?)0%; line-height:[^<]*?">([^<]*?)<(?=\/)\/span>/', '[size=\\1]\\2[/size]', $text);
		$text = preg_replace('/<a href="([^<]*?)" class="postlink[^<]*?" rel="nofollow" onclick="[^<]*?">([^<]*?)<\/a>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<div class="sp-wrap"><div class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/div><(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
	}

	$text = preg_replace('#\[url=http[^<]*?imdb.com/title/([^<]*?)\][^<]*?\[\/url\]#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)\].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)\].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);

	$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);

	// Вставка плеера
	insert_video_player($text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
