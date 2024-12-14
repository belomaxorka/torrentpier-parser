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
 * Парсер с only-soft.org (aka solely-soft.top)
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function onlysoft($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match('#<h1 class="maintitle">.*?<a class="tt-text" href=".*?">([\s\S]*?)</a>.*?</h1>#s', $text, $matches);
	$title = $matches[1];
	$title = str_replace('<wbr>', '', $title);

	// ------------------- Get download link -------------------
	preg_match('#<a href=\"download.php\?id=(.*?)\" class#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	$pos = strpos($text, '<span id="pp_');
	$text = substr($text, $pos);
	$pos = strpos($text, '<div id="pc_');
	$text = substr($text, 0, $pos);

	$text = preg_replace('#\n<h3 class="sp-title">.*?</h3>#', '', $text);
	$text = preg_replace('/<span id="pp_.*?">/', '', $text);
	$text = str_replace('<span class="post-br"><br /></span>', "\n\n", $text);

	$text = preg_replace('/<img class="smile" src=".*?" alt=".*?" align="absmiddle" border="0" \/>/', '', $text);
	$text = preg_replace('/\n<h3 class="sp-title">.*?<\/h3>/', '', $text);
	$text = str_replace('<div class="q-wrap">', '', $text);
	$text = str_replace('<div class="sp-wrap">', '', $text);
	$text = str_replace('<div class="c-wrap">', '', $text);
	$text = str_replace('<span class="post-hr">-</span>', "\n[hr]\n", $text);

	$text = preg_replace('/<var class="postImg" title="(.*?)">&#10;<\/var>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<var class="postImg postImgAligned img-(.*?)" title="(.*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
	$text = preg_replace('/<a href="([^<]*?)".*rel="topic" class="highslide"><img src=".*?".*?><\/a>/', '[th]$1[/th]', $text);
	$text = preg_replace('/\n/', "", $text);

	$text = str_replace('<ul>', '[list]', $text);
	$text = str_replace('</ul>', '[/list]', $text);
	$text = str_replace('<li>', "\n[*]", $text);
	$text = str_replace('</li>', '', $text);

	$text = str_replace('&#039;', "'", $text);
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace('&gt;', '>', $text);
	$text = str_replace('&lt;', '<', $text);
	$text = str_replace('<br />', "\n", $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<span class="post-b">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<span class="post-u">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<span class="post-i">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<span class="post-s">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
		$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
		$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
		$text = preg_replace('/<div style="margin-left: 2em">([^<]*?)<([^<]*?)\/div>/', "[list]$1[/list]", $text);
		$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<a rel="nofollow" href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<a href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<div class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/div>[^<]*?<(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
		$text = preg_replace('/<div class="q">([^<]*?)<(?=\/)\/div>([^<]*?)<([^<]*?)\/div>/', "[quote]\n\\1\n[/quote]", $text);
		$text = preg_replace('/<div class="q" head="([^<]*?)">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
		$text = preg_replace('/<div class="c-body">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[code]\n\\1\n[/code]", $text);

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
