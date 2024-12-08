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
 * Парсер с z-torrents.ru
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function ztorrents($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match("#<title>([\s\S]*?)</title>#i", $text, $matches);
	$title = $matches[1];
	$title = str_replace('скачать через торрент', '', $title);

	// ------------------- Get download link -------------------
	preg_match('#Скачать торрент: <a href=\"([\s\S]*?)\">#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	$pos = strpos($text, '<div class="fullstory">');
	$text = substr($text, $pos);
	$pos = strpos($text, '<div style="padding: 10px; border-bottom: 1px solid #dbe8ed;">');
	$text = substr($text, 0, $pos);

	$text = str_replace('<div class="fullstory">', '', $text);
	$text = str_replace('<br>', "\n", $text);

	$text = preg_replace('/<!--.*?-->/', '', $text);
	$text = preg_replace('/<img id=.*?>/', '', $text);

	$text = preg_replace_callback(
		"/<span style=\"color:rgb\(([\s\S]*?)\);\">/mi",
		function ($matches) {
			$rgb = $matches[1];
			$html_color = rgb2html($rgb);
			if ($html_color) {
				return "<span style=\"color:$html_color;\">";
			} else {
				return "<span style=\"color:rgb($rgb);\">";
			}
		}, $text);

	$text = str_replace('<div', '<span', $text);
	$text = str_replace('</div>', '</span>', $text);
	$text = str_replace('<a', '<span', $text);
	$text = str_replace('</a>', '</span>', $text);
	$text = str_replace('<sub>', "", $text);
	$text = str_replace('</sub>', "", $text);
	$text = str_replace('<sup>', "", $text);
	$text = str_replace('</sup>', "", $text);
	$text = str_replace('<tr>', '', $text);
	$text = str_replace('</tr>', '', $text);
	$text = preg_replace('/<td.*?>/', '', $text);
	$text = str_replace('</td>', '', $text);
	$text = str_replace('<ol>', '[list]', $text);
	$text = str_replace('</ol>', '[/list]', $text);
	$text = str_replace('<li>', "\n[*]", $text);
	$text = str_replace('</li>', '', $text);
	$text = str_replace('<ul>', '[list]', $text);
	$text = str_replace('</ul>', '[/list]', $text);
	$text = str_replace('youtube.com/embed/', "youtube.com/watch?v=", $text);
	$text = preg_replace('/<iframe width=".*?" height=".*?" src=\"(.*?)\" frameborder="0".*?><\/iframe>/', '[align=center][youtube]$1[/youtube][/align]', $text);

	$text = preg_replace('/<img src="([^<]*?)" style="max-width:100%;" alt=".*?">/', '[img]https://z-torrents.ru$1[/img]', $text);
	$text = preg_replace('/<img src="([^<]*?)" style="float:([^<]*?);max-width:100%;" alt=".*?" title=".*?">/', "\n[img=\\2]\\1[/img]\n", $text);
	$text = preg_replace('/<span style="margin-.*?;">([^<]*?)<(?=\/)\/span>/', '$1', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = str_replace('<u>', "[u]", $text);
		$text = str_replace('</u>', "[/u]", $text);
		$text = str_replace('<b>', "[b]", $text);
		$text = str_replace('</b>', "[/b]", $text);
		$text = str_replace('<i>', "[i]", $text);
		$text = str_replace('</i>', "[/i]", $text);
		$text = str_replace('<s>', "[s]", $text);
		$text = str_replace('</s>', "[/s]", $text);
		$text = str_replace('<hr>', '[hr]', $text);
		$text = preg_replace('/<span style="color:#([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=#$1]$2[/color]', $text);
		$text = preg_replace('/<span style="color:rgb([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<span style="text-align:([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[align=$1]$2[/align]', $text);
		$text = preg_replace('/<span style="font-family:([^<]*?),.*?;">([^<]*?)<(?=\/)\/span>/', '[font="$1"]$2[/font]', $text);
		$text = preg_replace('/<span style="font-size:([^<]*?)px;">([^<]*?)<(?=\/)\/span>/', '[size=\\1]\\2[/size]', $text);
		$text = preg_replace('/<span href="([^<]*?)" rel="external noopener noreferrer">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<span href="([^<]*?)" rel="noopener noreferrer external" target="_blank">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<span class="title_spoiler">.*?<span href=".*?">([^<]*?)<(?=\/)\/span><(?=\/)\/span><span id=".*?" class="text_spoiler" style="display:none;">([^<]*?)<(?=\/)\/span>([^<]*?)<(?=\/)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]\n", $text);
	}

	$text = preg_replace('/<span href=".*?">([\s\S]*?)<\/span>/', '$1', $text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
