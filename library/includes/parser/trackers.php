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
		'regex' => "#xxxtor\.net/\d+-\d+\.html#",
		'target_element' => '<div id="down">',
	),
	'rutracker_ru' => array(
		'enabled' => true,
		'auth' => true,
		'name' => 'rutracker.ru',
		'icon' => BB_ROOT . 'styles/images/trackers/rutracker_ru.ico',
		'regex' => "#rutracker\.ru/viewtopic\.php\?t=\d+#",
		'login_url' => 'http://rutracker.ru/login.php',
		'dl_url' => 'http://rutracker.ru/dl.php?id=',
		'login_input_name' => 'login_username',
		'password_input_name' => 'login_password',
		'target_element' => '<input type="radio" name=',
		'redirect' => array(
			// from https -> http
			'from' => array('https://rutracker.ru/'),
			'to' => 'http://rutracker.ru/'
		)
	),
	'ztorrents' => array(
		'enabled' => true,
		'name' => 'z-torrents.ru',
		'icon' => BB_ROOT . 'styles/images/trackers/z-torrents.ico',
		'regex' => "#z-torrents\.ru/[a-z]+/\d+-\S+\.html#",
		'target_element' => '<div class="dle_b_appp"',
	),
	'uniongang' => array(
		'enabled' => true,
		'auth' => true,
		'name' => 'uniongang.org',
		'icon' => BB_ROOT . 'styles/images/trackers/uniongang.ico',
		'regex' => "#uniongang\.org/details\.php\?id=\d+#",
		'login_url' => 'https://uniongang.org/takelogin.php',
		'dl_url' => 'https://uniongang.org/download.php?id=',
		'login_input_name' => 'username',
		'password_input_name' => 'password',
		'target_element' => '<form method="post" action="takerate.php">',
		'redirect' => array(
			'from' => array('https://uniongang.club/'),
			'to' => 'https://uniongang.org/'
		)
	),
);
