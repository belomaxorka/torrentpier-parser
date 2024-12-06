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
		'info' => array(
			'name' => 'rutor.info',
			'href' => 'http://rutor.info',
			'icon' => BB_ROOT . 'styles/images/trackers/rutor.ico',
		),
		'settings' => array(
			'regex' => "#(?:rutor\.info|rutor\.is)\/torrent/#", // .is, .info
			'dl_url' => 'http://d.rutor.info/download/',
			'target_element' => '<td class="header"',
		),
		'redirect' => array(
			'from' => array('http://rutor.org/'),
			'to' => 'http://rutor.info/',
		),
	),
	'xxxtor' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'xxxtor.net',
			'href' => 'http://xxxtor.net',
			'icon' => BB_ROOT . 'styles/images/trackers/xxxtor.ico',
		),
		'settings' => array(
			'regex' => "#xxxtor\.net/\d+-\d+\.html#",
			'target_element' => '<div id="down">',
		),
	),
	'rutracker_ru' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'rutracker.ru',
			'href' => 'http://rutracker.ru',
			'icon' => BB_ROOT . 'styles/images/trackers/rutracker_ru.ico',
		),
		'settings' => array(
			'regex' => "#rutracker\.ru/viewtopic\.php\?t=\d+#",
			'dl_url' => 'http://rutracker.ru/dl.php?id=',
			'target_element' => '<input type="radio" name=',
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'http://rutracker.ru/login.php',
			'login_input_name' => 'login_username',
			'password_input_name' => 'login_password',
		),
		'redirect' => array(
			// from https -> http
			'from' => array('https://rutracker.ru/'),
			'to' => 'http://rutracker.ru/',
		),
	),
	'ztorrents' => array(
		'enabled' => true,
		'name' => 'z-torrents.ru',
		'href' => 'http://z-torrents.ru',
		'icon' => BB_ROOT . 'styles/images/trackers/z-torrents.ico',
		'regex' => "#z-torrents\.ru/[a-z]+/\d+-\S+\.html#",
		'target_element' => '<div class="dle_b_appp"',
	),
	'booktracker' => array(
		'enabled' => true,
		'auth' => true,
		'name' => 'booktracker.org',
		'href' => 'http://booktracker.org',
		'icon' => BB_ROOT . 'styles/images/trackers/booktracker.ico',
		'regex' => "#booktracker\.org/viewtopic\.php\?t=\d+#",
		'login_url' => 'https://booktracker.org/login.php',
		'dl_url' => 'https://booktracker.org/download.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<div id="tor_info"',
	),
);
