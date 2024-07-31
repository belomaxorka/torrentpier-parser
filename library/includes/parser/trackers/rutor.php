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
 * Rutor.info Parser
 *
 * @param string $content
 * @param string $target_element
 * @return array
 * @throws Exception
 *
 * @license MIT License
 * @author belomaxorka
 *
 */
function rutor($content, $target_element)
{
	// Инициализация класса для работы с DOM
	$dom = new \IvoPetkov\HTML5DOMDocument();
	$dom->loadHTML($content);
	$html = $dom->querySelector($target_element)->innerHTML;

	// Основные замены
	$html = parser_base($html);

	// Удаление ссылок, с относительными ссылками
	$html = preg_replace('/<a href="\/.*?">(.*?)<\/a>/i', '$1', $html);

	// Вставка плеера
	insert_video_player($html);

	// Формирование выходных данных
	return array(
		'title' => $dom->querySelector('h1')->textContent,
		'torrent' => $dom->querySelector('a[href^="//d.rutor.info/download/"]')->getAttribute('href'),
		'content' => strip_tags($html)
	);
}
