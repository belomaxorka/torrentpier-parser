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
 * Парсер с ddgroupclub.win
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф, DimaUZB2001
 * @license MIT License
 */
function ddgroupclub($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match('#<title>(.*?)(::.*?)</title>#s', $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a href="dl.php\?id=(.*?)" class=".*?">#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	$pos = strpos($text, '<span id="pp_');
	$text = substr($text, $pos);
	$pos = strpos($text, '<div id="pc_');
	$text = substr($text, 0, $pos);
	$text = preg_replace('/<span id="pp_.*?">/', '', $text);
	$text = preg_replace('#<h3 class="sp-title">.*?</h3>#', '', $text);
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
	$text = preg_replace('/<var class="postImg postImgAligned img-([^<]*?)" title="([^<]*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
	$text = preg_replace('/<var class="postImg" title="([^<]*?)">&#10;<(?=\/)\/var>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
	$text = preg_replace('/<span href="([^<]*?)".*? rel="topic" class="highslide">.*?<\/span>/', '[thumb]$1[/thumb]', $text);

	$text = preg_replace('/<span href=".*?" target="_blank"><img src=".\/ratings\/kinopoisk.php\?url=([^<]*?)" ><\/span>/', '[kp]$1[/kp]', $text);
	$text = preg_replace('/<span href=".*?" target="_blank"><img src=".\/ratings\/imdb.php\?url=([^<]*?)" ><\/span>/', '[imdb]$1[/imdb]', $text);

	$text = preg_replace('/<span style="width:.*?"><span style="overflow:.*?">([^<]*?)<\/span><\/span>/', '[scroll]$1[/scroll]', $text);
	$text = preg_replace('/<iframe.*?src="([^<]*?)" frameborder="0" allowfullscreen><\/iframe>/', '[youtube]https:$1[/youtube]', $text);
	$text = str_replace('youtube.com/v/', 'youtube.com/watch?v=', $text);
	$text = str_replace('&hl=ru_RU&fs=1&', '', $text);
	$text = str_replace('youtube.com/embed/', "youtube.com/watch?v=", $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<span class="post-b">([\s\S]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<span class="post-u">([\s\S]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<span class="post-i">([\s\S]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<span class="post-s">([\s\S]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
		$text = preg_replace('/<span class="post-sh">([\s\S]*?)<(?=\/)\/span>/', '[sh]$1[/sh]', $text);
		$text = preg_replace('/<span class="post-d">([\s\S]*?)<(?=\/)\/span>/', '[d]$1[/d]', $text);
		$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([\s\S]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
		$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([\s\S]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
		$text = preg_replace('/<span style="color: ([^<]*?);">([\s\S]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<span href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<span class="sp-body" title="([^<]*?)">([\s\S]*?)<(?=\/)\/span>([\s\S]*?)<(?=\/)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
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
