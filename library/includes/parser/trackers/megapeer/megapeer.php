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
 * Парсер с megapeer.vip
 *
 * @param $text
 * @param object $curl
 * @param array $tracker_data
 * @return array
 * @author ivangord aka Ральф
 * @license MIT License
 */
function megapeer($text, $curl = null, $tracker_data = null)
{
	// ------------------- Get title -------------------
	preg_match("#<H1>([\s\S]*?)</H1>#", $text, $matches);
	$title = $matches[1];

	// ------------------- Get download link -------------------
	preg_match('#<a.*?href=\"/download/([\d]+)\".*?>#', $text, $matches);
	$torrent = $matches[1];

	// ------------------- Get content -------------------
	preg_match_all("#<tr><td style=\"vertical-align:top;\">([\s\S]*?)</td></tr>#si", $text, $source, PREG_SET_ORDER);
	$text = $source[0][1];
	$text = preg_replace("/<\/td><td><img src ='([^<]*?)'\/>/", '[img=right]$1[/img]', $text);
	$text = preg_replace('/<\/td><td>.*?<img src="([\s\S]*?)" \/>/', '[img=right]$1[/img]', $text);
	$text = preg_replace("/<img src = '(.*?)' style='float: [\d]+;'\/>/", '[img]$1[/img]', $text);

	$text = str_replace('<left>', '', $text);
	$text = str_replace('</left>', '', $text);
	$text = str_replace('<right>', '', $text);
	$text = str_replace('</right>', '', $text);

	$text = str_replace('<center>', '[align=center]', $text);
	$text = str_replace('</center>', '[/align]', $text);
	$text = str_replace('<hr />', '[hr]', $text);
	$text = str_replace('<hr/>', '[hr]', $text);
	$text = str_replace('<br />', "", $text);
	$text = preg_replace('/<a href="\/tag\/.*?" target="_blank">([\s\S]*?)<\/a>/', '$1', $text);
	$text = str_replace('<br>', "", $text);
	$text = str_replace('&#039;', "'", $text);
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace('&gt;', '>', $text);
	$text = str_replace('&lt;', '<', $text);
	$text = str_replace('<div class="sp-wrap">', '', $text);
	$text = preg_replace("/<img src = '(.*?)' style='float: (.*?);'\/>/", '[img=$2]$1[/img]', $text);
	$text = preg_replace('/<span style="color: #0000ff;text-decoration: underline;">.*?span>/', '', $text);

	for ($i = 0; $i <= 20; $i++) {
		$text = preg_replace("/<a href = '([^<]*?)' target='_blank' class='online'><span style.*?>([\s\S]*?)<(?=\/)\/span><(?=\/)\/a>/", '[url=$1]$2[/url]', $text);

		$text = preg_replace("/<a href=\"([^<]*?)\" target='_blank' class='online'><span style.*?>([\s\S]*?)<(?=\/)\/span><(?=\/)\/a>/", '[url=$1]$2[/url]', $text);

		$text = preg_replace("/<img src ='([^<]*?)'\/>/", '[img]$1[/img]', $text);
		$text = preg_replace('/<img src="([\s\S]*?)" \/>/', '[img]$1[/img]', $text);

		$text = preg_replace("/<span style = 'text-decoration:underline'>([^<]*?)<(?=\/)\/span>/", '[u]$1[/u]', $text);

		$text = preg_replace('/<b>([^<]*?)<(?=\/)\/b>/', '[b]$1[/b]', $text);
		$text = preg_replace('/<em>([^<]*?)<(?=\/)\/em>/', '[i]$1[/i]', $text);
		$text = preg_replace('/<s>([^<]*?)<(?=\/)\/s>/', '[s]$1[/s]', $text);
		$text = preg_replace("/<font size='([^<]*?)'>([\s\S]*?)<(?=\/)\/font>/", '[size=2\\1]\\2[/size]', $text);
		$text = preg_replace("/<span style = 'color: ([^<]*?)'>([^<]*?)<(?=\/)\/span>/", '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<span style="color: ([^<]*?);">([\s\S]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
		$text = preg_replace('/<span style="font-family: ([^<]*?);">([\s\S]*?)<(?=\/)\/span>/', '[font="$1"]$2[/font]', $text);
		$text = preg_replace('/<font size="([^<]*?)">([\s\S]*?)<(?=\/)\/font>/', '[size=2\\1]\\2[/size]', $text);

		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);

		$text = preg_replace('/<div class="sp-head folded clickable".*?>([^<]*?)<(?=\/)\/div><div class="sp-body" style="display: none;">([\s\S]*?)<(?=\/)\/div><(?=\/)\/div>/', '[spoiler="$1"]$2[/spoiler]', $text);
	}

	$text = preg_replace('#\[url=http.*?imdb.com/title/(.*?)].*?\[\/url\]#', '[imdb]https://www.imdb.com/title/$1[/imdb]', $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
	$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
	$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);

	// Вставка плеера
	insert_video_player($text);

	return array(
		'title' => $title,
		'torrent' => $torrent,
		'content' => strip_tags(html_entity_decode($text))
	);
}
