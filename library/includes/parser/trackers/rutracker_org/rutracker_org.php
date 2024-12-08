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
 * Парсер с rutracker.org
 *
 * @param $text
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 *
 */
function rutracker_org($text)
{
	// ------------------- Get title -------------------
	preg_match("#<title>(.*?)(::.*?)</title>#s", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a href="dl.php\?t=(.*?)" class="dl-stub dl-link.*?">.*?</a>#ms', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	preg_match("#<span class=\"txtb\" onclick=\"ajax.view_post\('(.*?)'\);\">#", $text, $matches);
	$post_id = $matches[1];

	preg_match("#/form_token.*?'(.*?)',/#", $text, $matches);
	$form_token = $matches[1];

	$post_data = array(
		"action" => 'view_post',
		"post_id" => "$post_id",
		"mode" => 'text',
		"form_token" => "$form_token"
	);

	// Вставка плеера
	insert_video_player($text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
