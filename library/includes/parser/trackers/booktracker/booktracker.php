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
 * Парсер с booktracker.org
 *
 * @param $text
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 *
 */
function booktracker($text)
{
	// ------------------- Get title -------------------
	preg_match("#<h1 class=\"maintitle\"><a href=\".*?\">([\s\S]*?)</a></h1>#", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a href=\"download.php\?id=(.*?)\" class#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	$pos = strpos($text, '<div class="post_body">');
	$text = substr($text, $pos);
	$pos = strpos($text, '<div class="spacer_8"></div>');
	$text = substr($text, 0, $pos);
	$text = str_replace('/<div class="clear"></div>/', '', $text);
	$text = str_replace('<wbr>', '', $text);

	$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
	$text = str_replace('<div class="clear"></div>', '', $text);
	$text = preg_replace('/<!--\/.*?-->/', '', $text);
	$text = str_replace('<hr />', "\n[hr]\n", $text);
	$text = preg_replace('/<var class="postImg" title="(.*?)">&#10;<\/var>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<var class="postImg postImgAligned img-(.*?)" title="(.*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
	$text = preg_replace('/<img src="(.*?)" id="postImg" class="postImg" align="absmiddle" hspace="0" vspace="4" alt="pic" \/>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<span style="text-shadow: #000 1px 1px 2px, black 0 0 1em;">(.*?)<\/span>/', '[sh]$1[/sh]', $text);
	$text = preg_replace('/<object .*? value="(.*?)".*?<\/object>/', '[youtube]$1[/youtube]', $text);
	$text = str_replace('youtube.com/v/', 'youtube.com/watch?v=', $text);
	$text = preg_replace('/<a href="([^<]*?)" target="_blank" \/>([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);

	$text = str_replace('<ul>', '[list]', $text);
	$text = str_replace('</ul>', '[/list]', $text);
	$text = str_replace('<li>', "\n[*]", $text);
	$text = str_replace('</li>', '', $text);
	$text = str_replace('<br /><br />', "\n\n", $text);
	$text = str_replace('<br clear="all" />', "\n[br]\n", $text);
	$text = str_replace('<div></div>', "\n", $text);
	$text = str_replace('<div', '<span', $text);
	$text = str_replace('</div>', '</span>', $text);
	$text = str_replace('<a', '<span', $text);
	$text = str_replace('</a>', '</span>', $text);
	$text = str_replace('&#039;', "'", $text);
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace('&gt;', '>', $text);
	$text = str_replace('&lt;', '<', $text);
	$text = str_replace('&#10;', '<', $text);
	$text = str_replace('&quot;', '', $text);
	$text = str_replace('&#10;', "'", $text);
	$text = preg_replace('/<!--\/.*?-->/', '', $text);
	$text = str_replace('<span class="sp-wrap">', '', $text);
	$text = str_replace('<span class="post_body">', '', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<span style="font-weight: bold;">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<span style="text-decoration: underline;">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<span style="font-style: italic;">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
		$text = preg_replace('/<span style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
		$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<span href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<span href="([^<]*?)" target="_blank" \/>([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<p class="q_head"><b>.*?<\/b><\/p>[\s\S]*?<div class="q">([^<]*?)<(?=\/)\/div><!--\/q-->/', "[quote]\n\\1\n[/quote]", $text);
		$text = preg_replace('/<p class="q_head"><b>(.*?)<\/b>.*?<\/p>[\s\S]*?<div class="q">([^<]*?)<(?=\/)\/div><!--\/q-->/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
		$text = preg_replace('/<span class="code">([^<]*?)<(?=\/)\/span>/', "[code]\n\\1\n[/code]", $text);
		$text = preg_replace('/<span class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
		$text = preg_replace('/<span class="sp-body">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[spoiler]\n\\1\n[/spoiler]", $text);
		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
	}

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
