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
 * Обновление страницы после die
 *
 * @param string $msg
 * @return void
 */
function die_and_refresh($msg)
{
	meta_refresh('release.php', 10);
	bb_die($msg);
}

/**
 * Получение содержимого страницы
 *
 * @param object $curl
 * @param string $url
 * @param string $target_element
 * @return mixed
 */
function fetch_content($curl, $url, $target_element)
{
	global $lang;

	// Получение контента
	$content = $curl->fetchUrl($url);
	$content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content));
	$pos = strpos($content, $target_element);
	$content = substr($content, 0, $pos);

	// Проверка на пустую страницу
	if (empty($content)) {
		die_and_refresh(sprintf($lang['PARSER_EMPTY_CONTENT'], $url));
	}

	return $content;
}

/**
 * Декодирование торрента
 *
 * @param string $torrent
 * @param string $info_hash
 * @return array
 */
function torrent_decode($torrent, &$info_hash)
{
	global $lang;

	$tor = null;

	if (function_exists('bencode')) {
		require_once INC_DIR . '/functions_torrent.php';
		$tor = bdecode($torrent);
		$info_hash = pack('H*', sha1(bencode($tor['info'])));
	} elseif (class_exists('\Rych\Bencode\Bencode')) {
		$tor = \Rych\Bencode\Bencode::decode($torrent);
		$info_hash = pack('H*', sha1(\Rych\Bencode\Bencode::encode($tor['info'])));
	} elseif (class_exists('\SandFox\Bencode\Bencode')) {
		$tor = \SandFox\Bencode\Bencode::decode($torrent);
		$info_hash = pack('H*', sha1(\SandFox\Bencode\Bencode::encode($tor['info'])));
	} elseif (class_exists('\Arokettu\Bencode\Bencode')) {
		// Раскомментировать для версий v2.4.0 и выше
		// $tor = \Arokettu\Bencode\Bencode::decode($torrent, dictType: \Arokettu\Bencode\Bencode\Collection::ARRAY);
		// $info_hash = pack('H*', sha1(\Arokettu\Bencode\Bencode::encode($tor['info'])));
	}

	if (!isset($tor) || !isset($info_hash)) {
		if (class_exists('\Arokettu\Bencode\Bencode')) {
			bb_die($lang['PARSER_NOT_FOUND_BENCODE_LIB_NEW']);
		} else {
			bb_die($lang['PARSER_NOT_FOUND_BENCODE_LIB']);
		}
	}

	if (empty($info_hash)) {
		bb_die($lang['PARSER_EMPTY_INFO_HASH']);
	}

	return $tor;
}

/**
 * Прикрепляем торрент-файл
 *
 * @param array $tor
 * @param string $torrent
 * @param string $hidden_form_fields
 * @return void
 */
function attach_torrent_file($tor, $torrent, &$hidden_form_fields)
{
	global $attach_dir, $lang;

	if (is_array($tor) && count($tor)) {
		// Создание торрент-файла
		$new_name = hash('md5', $torrent) . '_' . TIMENOW;
		$file_path = "$attach_dir/$new_name";
		$file = new SplFileInfo($file_path);
		if (!$file->isFile()) {
			file_put_contents($file_path, $torrent);
		} else {
			bb_die(sprintf($lang['PARSER_CANT_SAVE_TORRENT'], $file_path));
		}

		// Заполнение скрытых полей
		$hidden_form_fields .= '<input type="hidden" name="add_attachment_body" value="0" />';
		$hidden_form_fields .= '<input type="hidden" name="posted_attachments_body" value="0" />';
		$hidden_form_fields .= '<input type="hidden" name="attachment_list[]" value="' . basename($file_path) . '" />';
		$hidden_form_fields .= '<input type="hidden" name="filename_list[]" value="' . basename($file_path) . '.torrent" />';
		$hidden_form_fields .= '<input type="hidden" name="extension_list[]" value="torrent" />';
		$hidden_form_fields .= '<input type="hidden" name="mimetype_list[]" value="' . mime_content_type($file_path) . '" />';
		$hidden_form_fields .= '<input type="hidden" name="filesize_list[]" value="' . filesize($file_path) . '" />';
		$hidden_form_fields .= '<input type="hidden" name="filetime_list[]" value="' . TIMENOW . '" />';
		$hidden_form_fields .= '<input type="hidden" name="attach_id_list[]" value="0" />';
		$hidden_form_fields .= '<input type="hidden" name="attach_thumbnail_list[]" value="0" />';
	}
}

/**
 * Проверка на дубли
 *
 * @param string $info_hash
 * @param string $subject
 * @param string $url
 * @return void
 */
function duplicate_check($info_hash, $subject, $url)
{
	global $lang;

	$info_hash_sql = rtrim(DB()->escape($info_hash), ' ');
	if ($row = DB()->fetch_row("SELECT topic_id FROM " . BB_BT_TORRENTS . " WHERE info_hash = '$info_hash_sql' LIMIT 1")) {
		bb_die(sprintf($lang['PARSER_DUPLICATE_TORRENT'], $url, $subject, TOPIC_URL . $row['topic_id'], $subject));
	}
}

/**
 * Вставка видео
 *
 * @param string $html
 * @return void
 */
function insert_video_player(&$html)
{
	global $bb_cfg;

	if (!$bb_cfg['torrent_parser']['use_video_player']) {
		return;
	}

	// imdb
	preg_match("/imdb\.com\/title\/tt(\d+)/", $html, $has_imdb);
	$has_imdb = isset($has_imdb[1]) ? $has_imdb[1] : false; // В посте есть баннер imdb! Ура, победа!
	// kp
	preg_match("/kinopoisk\.ru\/(?:film|series|level\/\d+\/film)\/(\d+)/", $html, $has_kp);
	$has_kp = isset($has_kp[1]) ? $has_kp[1] : false; // В посте есть баннер kp! Ура, победа!
	// вставка плеера
	if (!empty($has_imdb) || !empty($has_kp)) {
		$html .= '[br][hr]';
		if (is_numeric($has_kp)) {
			// данные с кп приоритетнее
			$html .= '[movie=kinopoisk]' . $has_kp . '[/movie]';
		} elseif (is_numeric($has_imdb)) {
			$html .= '[movie=imdb]tt' . $has_imdb . '[/movie]';
		}
		$html .= '[hr][br]';
	}
}

/**
 * Конвертация RGB в HEX
 *
 * @param $r
 * @param $g
 * @param $b
 * @return string
 */
function rgb2html($r, $g = -1, $b = -1)
{
	if (is_array($r) && sizeof($r) == 3)
		list($r, $g, $b) = $r;

	$r = intval($r);
	$g = intval($g);
	$b = intval($b);

	$r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
	$g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
	$b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));

	$color = (strlen($r) < 2 ? '0' : '') . $r;
	$color .= (strlen($g) < 2 ? '0' : '') . $g;
	$color .= (strlen($b) < 2 ? '0' : '') . $b;
	return '#' . $color;
}
