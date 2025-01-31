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
 * Парсер с rutracker.ru
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function rutracker_ru($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match("#<title>(.*?)(::.*?)</title>#s", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a href="dl.php\?id=(.*?)" class="genmed">#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	$pos = strpos($text, '<span id="pp_');
	$text = substr($text, $pos);
	$pos = strpos($text, '<div id="pc_');
	$text = substr($text, 0, $pos);
	$text = preg_replace('/<span id="pp_.*?">/', '', $text);

	$text = str_replace('<div class="c-wrap">', '', $text);
	$text = str_replace('<div class="q-wrap">', '', $text);
	$text = str_replace('<ul>', '[list]', $text);
	$text = str_replace('</ul>', '[/list]', $text);
	$text = str_replace('<li>', "\n[*]", $text);
	$text = str_replace('<div', '<span', $text);
	$text = str_replace('</div>', '</span>', $text);
	$text = str_replace('<a', '<span', $text);
	$text = str_replace('</a>', '</span>', $text);
	$text = str_replace('https://href.li/?', '', $text);

	$text = str_replace('<span class="post-hr">-</span>', "\n[hr]\n", $text);
	$text = str_replace('<span class="post-br"><br /></span>', "\n[br]\n", $text);
	$text = str_replace('<br />', "\n", $text);
	$text = preg_replace('/<var class="postImg postImgAligned img-(.*?)" title="([^<]*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
	$text = preg_replace('/<var class="postImg" title="([^<]*?)">&#10;<(?=\/)\/var>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
	$text = preg_replace('/<span href="([^<]*?)".*? data-rel="lightcase:myCollection:slideshow">.*?<\/span>/', '[thumb]$1[/thumb]', $text);
	$text = preg_replace('/<object.*?><param name="movie" value="([^<]*?)"><\/param>[\s\S]*?<\/object>/', '[youtube]$1[/youtube]', $text);
	$text = str_replace('youtube.com/v/', 'youtube.com/watch?v=', $text);
	$text = str_replace('&hl=ru_RU&fs=1&', '', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<span class="post-b">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<span class="post-u">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<span class="post-i">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<span class="post-s">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
		$text = preg_replace('/<span class="post-sh">([^<]*?)<(?=\/)\/span>/', '[sh]$1[/sh]', $text);
		$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
		$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
		$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<span href="([^<]*?)" class="postLink" rel="nofollow">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<span class="sp-wrap"><span class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/span><(?=\/)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
		$text = preg_replace('/<span class="q">([^<]*?)<(?=\/)\/span>([^<]*?)<([^<]*?)\/span>/', "[quote]\n\\1\n[/quote]", $text);
		$text = preg_replace('/<span class="q" head="([^<]*?)">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
		$text = preg_replace('/<span class="c-body">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[code]\n\\1\n[/code]", $text);
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
