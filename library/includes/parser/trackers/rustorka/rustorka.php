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
	// Вставка плеера
	insert_video_player($text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
