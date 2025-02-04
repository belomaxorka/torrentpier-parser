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
 * Парсер с windows-soft.info
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function windowssoft($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match("#<h1 class=\"fstory-h1\">([\s\S]*?)</h1>#", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a href=\".*?engine/download.php\?id=(\d+)\" class=\"btn_red\">#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	preg_match_all('/<img src="([^<]*?)" style="height:\d+px;width:\d+px" alt="poster"\/>/', $text, $pic, PREG_SET_ORDER);
	$poster = ($pic[0][1]) ? "[img=right]" . $pic[0][1] . "[/img]\n" : "";

	preg_match_all("#<div class=\"fstory-content margin-b20\">([\s\S]*?)<center><center>#si", $text, $source, PREG_SET_ORDER);
	$text = $poster . $source[0][1];

	$text = preg_replace('/<!--sizestart:(\d+)--><span style="font-size.*?">/', '<size style="font-size:\\1">', $text);
	$text = preg_replace('/<\/span><!--\/sizeend-->/', "</size>", $text);
	$text = str_replace('&quot;', "", $text);
	$text = str_replace('&nbsp;', "", $text);
	$text = preg_replace('/<!--.*?-->/', '', $text);
	$text = preg_replace('/<!--\/.*?-->/', '', $text);
	$text = preg_replace('/<img id="image.*?>/', '', $text);
	$text = preg_replace('/<a href="javascript[\s\S]*?">/', '', $text);
	$text = preg_replace('/<div id=".*?" class="text_spoiler" style="display:none;">/', '<div class="text_spoiler">', $text);

	$text = preg_replace('/<img src="([^<]*?)" style=".*?;" data-maxwidth=".*?" alt=".*?">/', "[img]$1[/img]", $text);
	$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);
	$text = str_replace('<br>', "\n", $text);
	$text = preg_replace('/<div style="text-align:.*?;"><strong>Скачать.*?<\/strong><\/div>/', '', $text);
	$text = str_replace('youtube.com/embed/', "youtube.com/watch?v=", $text);
	$text = str_replace('<ul>', '[list]', $text);
	$text = str_replace('</ul>', '[/list]', $text);
	$text = str_replace('<li>', "\n[*]", $text);
	$text = str_replace('</li>', '', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = str_replace('<u>', "[u]", $text);
		$text = str_replace('</u>', "[/u]", $text);
		$text = str_replace('<b>', "[b]", $text);
		$text = str_replace('</b>', "[/b]", $text);
		$text = str_replace('<i>', "[i]", $text);
		$text = str_replace('</i>', "[/i]", $text);
		$text = str_replace('<s>', "[s]", $text);
		$text = str_replace('</s>', "[/s]", $text);

		$text = preg_replace('/<size style="font-size:([^<]*?)">([^<]*?)<(?=\/)\/size>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="color:([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<div style="text-align:([^<]*?);">([^<]*?)<(?=\/)\/div>/', '[align=$1]$2[/align]', $text);
		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
		$text = preg_replace('/<a href="([^<]*?)"  target="_blank".*?>([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<div class="title_spoiler">([\s\S]*?)<\/a><(?=\/)\/div><div class="text_spoiler">([^<]*?)<(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]\n", $text);
	}

	$text = preg_replace('#\[url=http.*?imdb.com/title/(.*?)/].*?\[\/url\]#', '[imdb]https://www.imdb.com/title/$17[/imdb]', $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
	$text = preg_replace('/\[url=.*?torrents-club.info.*?\].*?\[\/url\]/', "", $text);

	// Вставка плеера
	insert_video_player($text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
