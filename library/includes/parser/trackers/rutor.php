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

	// Картинки
	$html = preg_replace('/<img src="([^<]*?)" style="float:(.*?);" \/>/siu', '[img=$2]$1[/img]', $html);
	$html = preg_replace('/<img src="([^<]*?)" \/>/siu', '[img]$1[/img]', $html);

	// Удаление ссылок, с относительными ссылками
	$html = preg_replace('/<a href="\/.*?">(.*?)<\/a>/i', '$1', $html);

	// Спойлеры
	$html = preg_replace('/<div class="hidewrap"><div class="hidehead" onclick="hideshow.*?">([\s\S]*?)<\/div><div class="hidebody"><\/div><textarea class="hidearea">([\s\S]*?)<\/textarea><\/div>/', "[spoiler=\"\\1\"]\\2[/spoiler]", $html);

	// Вставка плеера
	insert_video_player($html);

	// Формирование выходных данных
	return array(
		'title' => $dom->querySelector('h1')->textContent,
		'torrent' => $dom->querySelector('a[href^="//d.rutor.info/download/"]')->getAttribute('href'),
		'content' => strip_tags($html)
	);
}
