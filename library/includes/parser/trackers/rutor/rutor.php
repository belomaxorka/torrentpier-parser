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
 * Парсер с Rutor.info
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author _Xz_
 * @license MIT License
 * @link https://torrentpier.com/resources/avtomaticheskij-parser-razdach-s-rutor-info.253/
 */
function rutor($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match("#<h1>([\s\S]*?)</h1>#", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match("#<a href=\".*?d.rutor.info/download/([\s\S]*?)\"><img src=\".*?down.png\"> .*? ([\s\S]*?).torrent</a>#", $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	preg_match("#<tr><td style=\"vertical-align:top;\"></td><td>([\s\S]*?)</td></tr>#si", $text, $matches);
	$text = $matches[1];

	$text = preg_replace('/<br.*?>/', "", $text);
	$text = preg_replace('/<a href="\/tag\/.*?" target="_blank">([\s\S]*?)<\/a>/', '$1', $text);
	$text = preg_replace('/<div class="hidewrap"><div class="hidehead" onclick="hideshow.*?">([\s\S]*?)<\/div><div class="hidebody"><\/div><textarea class="hidearea">([\s\S]*?)<\/textarea><\/div>/', "[spoiler=\"\\1\"]\\2[/spoiler]", $text);

	$text = str_replace('<center>', '[align=center]', $text);
	$text = str_replace('</center>', '[/align]', $text);
	$text = str_replace('<hr />', '[hr]', $text);

	$text = str_replace('&#039;', "'", $text);
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace('&gt;', '>', $text);
	$text = str_replace('&lt;', '<', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<a href="([^<]*?)" target="_blank">([^<]*?)<(?=\/)\/a>/siu', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<img src="([^<]*?)" style="float:(.*?);" \/>/siu', '[img=$2]$1[/img]', $text);
		$text = preg_replace('/<img src="([^<]*?)" \/>/siu', '[img]$1[/img]', $text);
		$text = preg_replace('/<b>([^<]*?)<(?=\/)\/b>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<u>([^<]*?)<(?=\/)\/u>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<i>([^<]*?)<(?=\/)\/i>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<s>([^<]*?)<(?=\/)\/s>/', '[s]$1[/s]', $text);
		$text = preg_replace('/<font size="([^<]*?)">([^<]*?)<(?=\/)\/font>/', "[size=2\\1]\\2[/size]", $text);
		$text = preg_replace('/<span style="color:([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<span style="font-family:([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[font="$1"]$2[/font]', $text);
	}

	// Вставка плеера
	insert_video_player($text);

	// Удаление последовательности [hr]
	$text = preg_replace('/\[hr](\[hr])+/', '[hr]', $text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
