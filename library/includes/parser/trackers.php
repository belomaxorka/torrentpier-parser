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
			'login_has_error_element' => '',
		),
		'redirect' => array(
			// from https -> http
			'from' => array('https://rutracker.ru/'),
			'to' => 'http://rutracker.ru/',
		),
	),
	'rutracker_org' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'rutracker.org',
			'href' => 'https://rutracker.org',
			'icon' => BB_ROOT . 'styles/images/trackers/rutracker_org.ico',
		),
		'settings' => array(
			'regex' => "#rutracker\.org/forum\/viewtopic\.php\?t=\d+#",
			'dl_url' => 'https://rutracker.org/forum/dl.php?t=',
			'ajax_url' => 'https://rutracker.org/forum/ajax.php',
			'target_element' => '<div style="padding-top: 6px;">',
			'from_win_1251_iconv' => true,
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'https://rutracker.org/forum/login.php',
			'login_input_name' => 'login_username',
			'password_input_name' => 'login_password',
			'login_has_error_element' => '/<img[^>]*src="https:\/\/static\.rutracker\.cc\/captcha\/[^"]*"\s*[^>]*>/i',
		),
	),
	'ztorrents' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'z-torrents.ru',
			'href' => 'http://z-torrents.ru',
			'icon' => BB_ROOT . 'styles/images/trackers/z-torrents.ico',
		),
		'settings' => array(
			'regex' => "#z-torrents\.ru/[a-z]+/\d+-\S+\.html#",
			'target_element' => '<div class="dle_b_appp"',
		),
	),
	'booktracker' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'booktracker.org',
			'href' => 'http://booktracker.org',
			'icon' => BB_ROOT . 'styles/images/trackers/booktracker.ico',
		),
		'settings' => array(
			'regex' => "#booktracker\.org/viewtopic\.php\?t=\d+#",
			'dl_url' => 'https://booktracker.org/download.php?id=',
			'target_element' => '<div id="tor_info"',
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'https://booktracker.org/login.php',
			'login_input_name' => 'login_username',
			'password_input_name' => 'login_password',
			'login_has_error_element' => '',
		),
	),
);
