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
			'name' => 'xxxtor.com',
			'href' => 'https://xxxtor.com',
			'icon' => BB_ROOT . 'styles/images/trackers/xxxtor.ico',
		),
		'settings' => array(
			'regex' => "#https?://(?:www\.)?(?:xxxtor\.(?:com|info|org))/torrent/\d+/#", // поддержка зеркал
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
			'regex' => "#riperam\.org\/[a-z-]+[a-z]+\/\S+-t\d+\.html$#",
			'dl_url' => 'https://riperam.org/download/file.php?id=',
			'target_element' => '<td style="text-align: center; vertical-align: top;">',
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'https://riperam.org/ucp.php?mode=login',
			'login_input_name' => 'username',
			'password_input_name' => 'password',
			'login_has_error_element' => '/<div class="error">.*?<\/div>/s',
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
			'login_has_error_element' => '/<div class="red">\s*(?:Не найдено имя|Неверно указан пароль для имени)[\s\S]*?<\/div>/s',
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
	'rintor' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'Rintor',
			'href' => 'https://rintor.org',
			'icon' => BB_ROOT . 'styles/images/trackers/rintor.ico',
		),
		'settings' => array(
			'regex' => "#rintor\.org/viewtopic\.php\?t=\d+#",
			'dl_url' => 'https://rintor.org/dl.php?id=',
			'target_element' => '<p class="small">',
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'https://rintor.org/login.php',
			'login_input_name' => 'login_username',
			'password_input_name' => 'login_password',
			'login_has_error_element' => '/<h4 class="warnColor1 tCenter mrg_16">.*?<\/h4>/s',
		),
	),
	'xxxtorrents' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'XXXTorrents',
			'href' => 'https://xxxtorrents.pro',
			'icon' => BB_ROOT . 'styles/images/trackers/xxxtorrents.ico',
		),
		'settings' => array(
			'regex' => "#xxxtorrents\.pro/viewtopic\.php\?t=\d+#",
			'dl_url' => 'https://xxxtorrents.pro/dl.php?id=',
			'target_element' => '<p class="small">',
		),
		'auth' => array(
			'enabled' => true,
			'login_url' => 'https://xxxtorrents.pro/login.php',
			'login_input_name' => 'login_username',
			'password_input_name' => 'login_password',
			'login_has_error_element' => '/<h4 class="warnColor1 tCenter mrg_16">.*?<\/h4>/s',
		),
	),
	'powertracker' => array(
		'enabled' => true,
		'info' => array(
			'name' => 'CyberTorrent',
			'href' => 'http://cybertorrent.pro',
			'icon' => BB_ROOT . 'styles/images/trackers/powertracker.ico',
		),
		'settings' => array(
			'regex' => "#cybertorrent\.pro/viewtopic\.php\?t=\d+#",
			'dl_url' => 'http://cybertorrent.pro/dl.php?id=',
			'target_element' => '<p class="small">',
		),
		'redirect' => array(
			// from https -> http
			'from' => array('https://cybertorrent.pro'),
			'to' => 'http://cybertorrent.pro',
		),
	),
);
