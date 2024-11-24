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
 * Парсер с uniongang.org
 *
 * @param $text
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 *
 */
function uniongang($text)
{
	// ------------------- Get title -------------------
	preg_match("#<a class=\"index\" href=\"download.php\?id=.*?\"><b>([\s\S]*?)</b></a>#", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a class=\"index\" href=\"download.php\?id=(\d+)\">#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	preg_match_all("#\" /><br />([\s\S]*?)<tr><td width=\"\" class=\"heading\" valign=\"top\" align=\"right\">Тип</td>#", $text, $source, PREG_SET_ORDER);
	preg_match_all('/<img class="linked-image" src="([^<]*?)" border="0"/', $text, $pic, PREG_SET_ORDER);
	$poster = ($pic[0][1]) ? "[img=right]" . $pic[0][1] . "[/img]" : "";
	$text = $poster . $source[0][1];
	$text = preg_replace('/<script type="text\/javascript">[\s\S]*?<\/script>/', '', $text);
	$text = preg_replace('/<script.*?script>/', '', $text);
	$text = preg_replace('/<td.*?>.*?<\/td>/', '', $text);
	$text = preg_replace('/<embed.*?embed>/', '', $text);
	$text = str_replace('<div class="clearer"></div>', '', $text);
	$text = str_replace('<br />', "", $text);

	$text = str_replace('<center>', "[align=center]", $text);
	$text = str_replace('</center>', "[/align]", $text);

	$text = str_replace('<textarea>', '', $text);
	$text = str_replace('</textarea>', '', $text);
	$text = str_replace('<noindex>', '', $text);
	$text = str_replace('</noindex>', '', $text);
	$text = preg_replace('/<div class="galPicList daGallery">([\s\S]*?)<\/div>/', "[spoiler=\"Скриншоты\"]\n[align=center]\\1[/align][/spoiler]", $text);

	$text = preg_replace('/<a href="http[^<]*?kinopoisk.ru\/film\/(\d+)\/".*?><img.*?><(?=\/)\/a>/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
	$text = preg_replace('/<a href="http[^<]*?kinopoisk.ru\/film.*?-[0-9]{4}-\/(\d+)\/".*?><img.*?><(?=\/)\/a>/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
	$text = preg_replace('/<a href="http[^<]*?kinopoisk.ru\/level\/.*?\/film\/(\d+)\/".*?><img.*?><(?=\/)\/a>/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
	$text = preg_replace('/<a href="http[^<]*?imdb.com\/title\/(\w+\d+)\/".*?><img.*?><(?=\/)\/a>/', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);

	$text = preg_replace('/<object .*? value=\'([^<]*?)\'.*?<\/object>/', '[youtube]$1[/youtube]', $text);
	$text = str_replace('youtube.com/v/', 'youtube.com/watch?v=', $text);
	$text = preg_replace('/&hl=ru&fs=1&/', '', $text);
	$text = preg_replace('/<img class="linked-image" src="([^<]*?)".*?\/>/', "[img]\\1[/img]", $text);
	$text = preg_replace('/<img border="0" src=".*?">/', '', $text);
	$text = preg_replace('/<a href="[^<]*?uniongang.[^<]*?" title=".*?">[^<]*?<\/a>/', '', $text);

	$text = str_replace('&#039;', "'", $text);
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace('&gt;', '>', $text);
	$text = str_replace('&lt;', '<', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<b>([^<]*?)<(?=\/)\/b>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<u>([^<]*?)<(?=\/)\/u>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<i>([^<]*?)<(?=\/)\/i>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<s>([^<]*?)<(?=\/)\/s>/', '[s]$1[/s]', $text);
		$text = preg_replace('/<a href="([^<]*?)" class="gPic" rel="galI".*?><img.*?><\/a>/', "[th]$1[/th]", $text);
		$text = preg_replace('/<font style="font-size: ([^<]*?)pt">([^<]*?)<(?=\/)\/font>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
		$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\\2[/align]", $text);
		$text = preg_replace('/<span style="color: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<a href="([^<]*?)" tooltip=".*?">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<a href="([^<]*?)" title=".*?">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<a href="\/([^<]*?)">([^<]*?)<(?=\/)\/a>/', '[url=http://www.uniongang.tv/$1]$2[/url]', $text);
		$text = preg_replace('/<a href="\/([^<]*?)">([^<]*?)<(?=\/)\/a>/', '[url=http://www.uniongang.club/$1]$2[/url]', $text);
		$text = preg_replace('/<div class="spoiler-wrap"><div class="spoiler-head folded clickable">([^<]*?)<\/div><div class="spoiler-body">([^<]*?)<(?=\/)\/div><(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
	}

	$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
	$text = str_replace('</td></tr><tr>', '', $text);
	$text = preg_replace('/<td valign=middle align=right>(.*?)<\/td><td>/', "\n\n\\1: ", $text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
