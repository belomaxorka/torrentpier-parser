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
 * ------------------------------------------------------
 * Список доступных трекеров
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * Описание структуры массива можно почитать тут:
 * https://torrentpier.com/threads/nextgen-parser-razdach.42297/post-96591
 * ------------------------------------------------------
 */
$trackers = array(
	'rutor' => array(
		'enabled' => true,
		'name' => 'Rutor.info',
		'icon' => BB_ROOT . 'styles/images/trackers/rutor.ico',
		'regex' => "#(?:rutor\.info|rutor\.is)\/torrent/#", // .is, .info
		'dl_url' => 'http://d.rutor.info/download/',
		'target_element' => '<td class="header"',
		'redirect' => array(
			'from' => array('http://rutor.org/'),
			'to' => 'http://rutor.info/'
		)
	),
	'xxxtor' => array(
		'enabled' => true,
		'name' => 'xxxtor.net',
		'icon' => BB_ROOT . 'styles/images/trackers/xxxtor.ico',
		'regex' => "#xxxtor.net#",
		'dl_url' => 'http://d.rutor.info/download/',
		'target_element' => '<div id="down">',
	),
);
