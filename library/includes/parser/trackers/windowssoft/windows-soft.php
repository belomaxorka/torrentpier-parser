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
 * Парсер с windows-soft.info
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function windowssoft($text, $curl = null, $tracker_data = null)
{
	global $lang;

	// ------------------- Get title -------------------
	preg_match("#<title>(.*?)(::.*?)</title>#s", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a href="dl.php\?t=(.*?)" class="dl-stub dl-link.*?">.*?</a>#ms', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	preg_match("#<span class=\"txtb\" onclick=\"ajax.view_post\('(.*?)'\);\">#", $text, $matches);
	$post_id = $matches[1];

	preg_match("/form_token.*?'(.*?)',/", $text, $matches);
	$form_token = $matches[1];

	$post_data = array(
		"action" => 'view_post',
		"post_id" => "$post_id",
		"mode" => 'text',
		"form_token" => "$form_token"
	);

	// Получение кода топика
	$source = $curl->sendPostData($tracker_data['settings']['ajax_url'], $post_data);
	if (!is_json($source)) {
		die_and_refresh($lang['PARSER_INVALID_JSON_RESPONSE']);
	}

	$text = json_decode(mb_convert_encoding($source, 'UTF-8', mb_detect_encoding($source)), true);
	$text = isset($text['post_text']) ? $text['post_text'] : '';

	// Проверка ответа
	if (empty($text)) {
		die_and_refresh($lang['PARSER_INVALID_JSON_RESPONSE']);
	}

	$text = preg_replace("#\[font=(\w+)]#", "[font=\"\\1\"]", $text);
	$text = preg_replace("#\[font=\"'(.+)'\"]#", "[font=\"\\1\"]", $text);

	// Вставка плеера
	insert_video_player($text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
