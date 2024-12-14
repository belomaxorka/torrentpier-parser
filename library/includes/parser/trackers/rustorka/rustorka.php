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
 * Парсер с rustorka.com
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function rustorka($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match("#<h1 class=\"bigtitle\"><a href=\".*?\">([\s\S]*?)</a></h1>#", $text, $matches);
	$title = $matches[1];
	$title = str_replace('<wbr>', '', $title);

	// ------------------- Get download link -------------------
	preg_match('#<th colspan=\"3\" class=\"genmed\">(.*?).torrent</th>[\s\S]*?<a href=\"download.php\?id=(.*?)\" #', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	$pos = strpos($text, '<div class="post_body"');
	$text = substr($text, $pos);
	$pos = strpos($text, '<div class="spacer_8"></div>');
	$text = substr($text, 0, $pos);
	$text = preg_replace('/<div class="post_body">/', '', $text);

	$text = str_replace('<wbr>', '', $text);
	$text = preg_replace('/<a href="http:\/\/rustorka.com\/forum\/search.*?nm=.*?" class="postLink">(.*?)<\/a>/', '$1', $text);
	$text = str_replace('/go.php?url=', '', $text);

	$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
	$text = str_replace('<div class="clear"></div>', '', $text);
	$text = preg_replace('/<!--\/.*?-->/', '', $text);
	$text = str_replace('<div class="spoiler-wrap">', '', $text);
	$text = str_replace('<div class="code_wrap">', '', $text);
	$text = str_replace('<div class="spoiler-body"></span></span></span></span>', '<span class="spoiler-body1">', $text);

	$text = str_replace('<hr />', "\n[hr]\n", $text);
	$text = preg_replace('/<div class="postImg-wrap" style=".*?" align="center"><img src="([^<]*?)" id="postImgAligned" class="postImg" alt="pic" \/><\/div>/', "[align=center][img]\\1[/img][/align]\n", $text);
	$text = preg_replace('/<div class="postImg-wrap" style=".*?" align="([^<]*?)"><img src="([^<]*?)" id="postImgAligned" class="postImg" alt="pic" \/><\/div>/', "[img=\\1]\\2[/img]\n", $text);
	$text = preg_replace('/<img src="([^<]*?)" id="postImg" class="postImg" align="absmiddle" hspace="0" vspace="4" alt="pic" \/>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<a href="([^<]*?)" rel=".*?" class="zoom"><img src="[^<]*?".*?<(?=\/)\/a>/si', "[th]$1[/th]", $text);
	$text = preg_replace('/<a href="([^<]*?)" target="_blank" \/><img.*?src="kinopoisk.php\?id=.*?".*?><\/a>/', "[kp]$1[/kp]", $text);

	$text = preg_replace('/<iframe width=".*?" height=".*?" src=\"([^<]*?)\" frameborder="0" allowfullscreen><\/iframe>/', '[align=center][youtube]$1[/youtube][/align]', $text);
	$text = str_replace('<b>', "[b]", $text);
	$text = str_replace('</b>', "[/b]", $text);

	$text = str_replace('<ul>', '[list]', $text);
	$text = str_replace('</ul>', '[/list]', $text);
	$text = str_replace('<li>', "\n[*]", $text);
	$text = str_replace('</li>', '', $text);
	$text = str_replace('<br />', "\n", $text);
	$text = str_replace('<br clear="all" />', "\n[br]\n", $text);
	$text = str_replace('<div></div>', "\n", $text);
	$text = preg_replace('/<div class="code_head">.*?<script type="text\/javascript">.*?<\/script>.*?<\/div>/', "", $text);

	$text = str_replace('<div', '<span', $text);
	$text = str_replace('</div>', '</span>', $text);
	$text = str_replace('<a', '<span', $text);
	$text = str_replace('</a>', '</span>', $text);
	$text = str_replace('&#039;', "'", $text);
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace('&gt;', '>', $text);
	$text = str_replace('&lt;', '<', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<span style="font-weight: bold;">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<span style="text-decoration: underline;">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<span style="text-shadow:  1px 1px 3px [^<]*?">([^<]*?)<(?=\/)\/span>/', '[sh]$1[/sh]', $text);
		$text = preg_replace('/<span style="font-style: italic;">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
		$text = preg_replace('#<param name="movie" value="(.*?)"></param>#', "[align=center][youtube]$1[/youtube][/align]", $text);
		$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru/", $text);
		$text = preg_replace('/<span href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<p class="q_head"><b>.*?<\/b><\/p>[\s\S]*?<div class="q">([^<]*?)<(?=\/)\/div><!--\/q-->/', "[quote]\n\\1\n[/quote]", $text);
		$text = preg_replace('/<p class="q_head"><b>(.*?)<\/b>.*?<\/p>[\s\S]*?<div class="q">([^<]*?)<(?=\/)\/div><!--\/q-->/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
		$text = preg_replace('/<span class="code">([\s\S]*?)<(?=\/)\/span>/', "[code]\n\\1\n[/code]", $text);
		$text = preg_replace('/<span class="spoiler-head folded clickable nowrap">([^<]*?)<(?=\/)\/span>.*?<span class="spoiler-body">([^<]*?)<(?=\/)\/span>([^<]*?)<(?=\/)\/span>/', "\n[spoiler=\"\\1\"]\n\\2\n[/spoiler]\n", $text);
		$text = preg_replace('/<span class="spoiler-head folded clickable nowrap">([^<]*?)<(?=\/)\/span>.*?<span class="spoiler-body1">([\s\S]*?)<(?=\/)\/span>([^<]*?)<(?=\/)\/span>([^<]*?)<(?=\/)\/span>/', "\n[spoiler=\"\\1\"]\n\\2\n[/spoiler]\n", $text);
		$text = preg_replace('/<span style="text-align: ([^<]*?);">([\s\S]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
		$text = preg_replace('#\[url=http.*?imdb.com/title/(\w+\d+)/].*?\[\/url\]#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(\d+)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(\d+)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(\d+)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(\d+)].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
	}

	// Вставка плеера
	insert_video_player($text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
