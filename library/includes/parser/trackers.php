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
		'regex' => "#(?:rutor\.info|rutor\.is)\/torrent/#", // .is, .info
		'target_element' => 'td[style="vertical-align:top;"] + td',
		'redirect' => array(
			'from' => array('http://rutor.org/'),
			'to' => 'http://rutor.info/'
		)
	),
);
