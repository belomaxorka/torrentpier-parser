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
			'login_has_error_element' => '/<div class="info_msg_wrap">.*?<\/div>/s',
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
			'login_has_error_element' => '/<h4 class="warnColor1 tCenter mrg_16">.*?<\/h4>/s',
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
			'login_has_error_element' => '/<h4 class="warnColor1 tCenter mrg_16">.*?<\/h4>/s',
		),
	),
	'riperam' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'riperam.org',
			'href' => 'https://riperam.org',
			'icon' => BB_ROOT . 'styles/images/trackers/riperam.ico',
		),
		'settings' => array(
			'regex' => "#riperam.org/#", // todo
			'dl_url' => 'https://riperam.org/download/file.php?id=',
			'target_element' => '<td style="text-align: center; vertical-align: top;">',
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'https://riperam.org/ucp.php?mode=login',
			'login_input_name' => 'username',
			'password_input_name' => 'password',
			'login_has_error_element' => '', // todo
		),
	),
	'kinozal' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'kinozal.tv',
			'href' => 'https://kinozal.tv',
			'icon' => BB_ROOT . 'styles/images/trackers/kinozal.ico',
		),
		'settings' => array(
			'regex' => "#kinozal\.tv/details\.php\?id=\d+#",
			'dl_url' => 'https://dl.kinozal.tv/download.php?id=',
			'target_element' => '<form id="cmt" method=post',
			'from_win_1251_iconv' => true,
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'https://kinozal.tv/takelogin.php',
			'login_input_name' => 'username',
			'password_input_name' => 'password',
			'login_has_error_element' => '/<div class="red">\s*Не найдено имя[\s\S]*?<\/div>/s',
		),
	),
	'windowssoft' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'windows-soft.info',
			'href' => 'https://windows-soft.info',
			'icon' => BB_ROOT . 'styles/images/trackers/windowssoft.ico',
		),
		'settings' => array(
			'regex' => "/^windows-soft\.info\/\d+-[a-z0-9-]+[a-z0-9-]+\.html$/",
			'dl_url' => 'https://windows-soft.info/engine/download.php?id=',
			'target_element' => '<div class="fstory-rating">',
		),
	),
	'ddgroupclub' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'ddgroupclub.win',
			'href' => 'http://ddgroupclub.win',
			'icon' => BB_ROOT . 'styles/images/trackers/ddgroupclub.ico',
		),
		'settings' => array(
			'regex' => "#ddgroupclub\.win/viewtopic\.php\?t=\d+#",
			'dl_url' => 'http://ddgroupclub.win/dl.php?id=',
			'target_element' => '<p class="small">',
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'http://ddgroupclub.win/login.php',
			'login_input_name' => 'login_username',
			'password_input_name' => 'login_password',
			'login_has_error_element' => '/<div class="info_msg_wrap">.*?<\/div>/s',
		),
		'redirect' => array(
			// from https -> http
			'from' => array('https://ddgroupclub.win/'),
			'to' => 'http://ddgroupclub.win/',
		),
	),
	'megapeer' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'megapeer.vip',
			'href' => 'https://megapeer.vip',
			'icon' => BB_ROOT . 'styles/images/trackers/megapeer.ico',
		),
		'settings' => array(
			'regex' => "/megapeer\.vip\/torrent\/\d+\/[\w_-]+/",
			'dl_url' => 'https://megapeer.vip/download/',
			'target_element' => '<td class="heading"',
			'from_win_1251_iconv' => true,
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'https://megapeer.vip/takelogin.php',
			'login_input_name' => 'username',
			'password_input_name' => 'password',
			'login_has_error_element' => '/<h1[^>]*>\s*Ошибка[\s\S]*?<\/h1>/i',
		),
		'redirect' => array(
			'from' => array('http://megapeer.ru/', 'https://megapeer.ru/'),
			'to' => 'https://megapeer.vip/',
		),
	),
);
