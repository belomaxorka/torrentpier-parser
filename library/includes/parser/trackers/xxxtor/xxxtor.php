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

if (!defined('BB_ROOT')) die(basename(__FILE__));

/**
 * Парсер с Rutor.info
 *
 * @link https://torrentpier.com/resources/avtomaticheskij-parser-razdach-s-rutor-info.253/
 * @author _Xz_
 * @license MIT License
 *
 * @param $text
 * @return array
 */
function rutor($text)
{
	// ------------------- Get title -------------------
	preg_match("#<h1>([\s\S]*?)</h1>#i", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a class="yellowBtn" href=".*?var=//(.*?)&.*?var2.*?</a>#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	$pos = strpos($text, '<div class="post-user-message">');
	$text = substr($text, $pos);
	$pos = strpos($text, '<script type="text/javascript">');
	$text = substr($text, 0, $pos);
	$text = preg_replace('/<div class="post-user-message">/', '', $text);
	$text = str_replace('<br />', "\n", $text);
	$text = preg_replace('/<var class="postImg" title="([^<]*?)">&#10;<\/var>/', '[img]$1[/img]', $text);
	$text = preg_replace('/<var class="postImg postImgAligned img-([^<]*?)" title="([^<]*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
	$text = str_replace('<div class="sp-wrap">', '', $text);
	$text = preg_replace('/<h3 class="sp-title">.*?<\/h3>/', '', $text);

	$text = str_replace('<span class="post-hr">-</span>', "\n[hr]\n", $text);
	$text = str_replace('<ol style="list-style: disc;">', '[list]', $text);
	$text = str_replace('</ol>', '[/list]', $text);
	$text = str_replace('<div', '<span', $text);
	$text = str_replace('</div>', '</span>', $text);
	$text = str_replace('<a', '<span', $text);
	$text = str_replace('</a>', '</span>', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace('/<span class="post-b">([\s\S]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<span class="post-u">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
		$text = preg_replace('/<span class="post-i">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<span class="post-s">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
		$text = preg_replace('/<span class="post-br">([^<]*?)<(?=\/)\/span>/', "\n\n$1", $text);
		$text = preg_replace('/<span class="post-color-text" style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[color=\\1]\\2[/color]", $text);
		$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
		$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);" data-.*?>([^<]*?)<(?=\/)\/span>/', '[align=$1]$2[/align]', $text);
		$text = preg_replace('/<span href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<span class="sp-body">([\s\S]*?)<(?=\/)\/span>/', "[spoiler]\n\\1\n[/spoiler]", $text);
		$text = preg_replace('/<span class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/span>[^<]*?<([^<]*?)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
	}

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
