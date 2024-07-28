<?php

if (!defined('BB_ROOT')) die(basename(__FILE__));

/**
 * Вставка видео
 *
 * @param $text
 * @return void
 */
function insert_video_player(&$text)
{
	global $bb_cfg;

	if (!$bb_cfg['torrent_parser']['use_video_player']) {
		return;
	}

	// imdb
	preg_match("/imdb\.com\/title\/tt(\d+)/", $text, $has_imdb);
	$has_imdb = isset($has_imdb[1]) ? $has_imdb[1] : false; // В посте есть баннер imdb! Ура, победа!
	// kp
	preg_match("/kinopoisk\.ru\/(?:film|series)\/(\d+)/", $text, $has_kp);
	$has_kp = isset($has_kp[1]) ? $has_kp[1] : false; // В посте есть баннер kp! Ура, победа!
	// вставка плеера
	if (!empty($has_imdb) || !empty($has_kp)) {
		$text .= '[br][hr]';
		if (is_numeric($has_kp)) {
			// данные с кп приоритетнее
			$text .= '[movie=kinopoisk]' . $has_kp . '[/movie]';
		} elseif (is_numeric($has_imdb)) {
			$text .= '[movie=imdb]' . $has_imdb . '[/movie]';
		}
		$text .= '[hr][br]';
	}
}

function rutracker($text, $mode = '')
{
	global $bb_cfg;

	$server_name = $bb_cfg['server_name'];
	$sitename = $bb_cfg['sitename'];

	if ($mode == 'title') {
		preg_match_all('#<title>([\s\S]*?) :: RuTracker.org</title>#', $text, $source, PREG_SET_ORDER);
		$text = @$source[0][1];
		$text = str_replace('<wbr>', '', $text);
	} elseif ($mode == 'torrent') {
		preg_match_all('#<a href="dl.php\?t=(.*?)" class="dl-stub dl-link.*?">.*?</a>#ms', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		$curl = new \Dinke\CurlHttpClient;

		preg_match_all("#<span class=\"txtb\" onclick=\"ajax.view_post\('(.*?)'\);\">#", $text, $id, PREG_SET_ORDER);
		$post_id = $id[0][1];

		preg_match_all("/form_token.*?'(.*?)',/", $text, $token, PREG_SET_ORDER);
		$form_token = $token[0][1];

		$post_data = array(
			"action" => "view_post",
			"post_id" => "$post_id",
			"mode" => "text",
			"form_token" => "$form_token"
		);

		$url = 'https://rutracker.org/forum/ajax.php';
		$curl->storeCookies(COOKIES_PARS_DIR . '/rutracker_cookie.txt');
		$source = $curl->sendPostData($url, $post_data);
		$text = $source;
		//dump($text);

		preg_match_all("#\{\"post_(.*?)\"#", $text, $id, PREG_SET_ORDER);
		$post_ajax = $id[0][1];
		//dump($post_ajax);

		$post = strpos($text, $post_ajax);
		$post_ajax = substr($post_ajax, 0, $post);
		if (!$post_ajax) {
			meta_refresh('release.php', '2');
			bb_die('Занято ;) - Приходите через 20 минут.');
		}

		$text = preg_replace('#{"post_text":"([\s\S]+)",#', '\\1', $text);
		$text = preg_replace('#"post_id":".;*?","action":"view_post"}#', '', $text);
		$text = preg_replace('#","post_id":".*?"action":"view_post"}#', '', $text);
		$text = preg_replace("#\[font=(\w+)]#", "[font=\"\\1\"]", $text);
		$text = preg_replace("#\[font=\"'(.+)'\"]#", "[font=\"\\1\"]", $text);
		$text = str_replace('\n', "\n", $text);
		$text = preg_replace('#\[box.*?\]#', "", $text);
		$text = str_replace('[/box]', "", $text);
		$text = str_replace('\"', '"', $text);
		/*
						$text = preg_replace('/\[url=http.*?imdb.com\/title\/(.*?)\/]\[img].*?\[\/img]\[\/url]/', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
						$text = preg_replace('/\[url=http.*?kinopoisk.ru\/film\/.*?-[0-9]{4}-(.*?)\/]\[img].*?\[\/img]\[\/url]/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
						$text = preg_replace('/\[url=http.*?kinopoisk.ru\/level\/.*?\/film\/(.*?)\/]\[img].*?\[\/img\]\[\/url]/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
						$text =  preg_replace('/\[url=http.*?kinopoisk.ru\/film\/(\d+)\/]\[img].*?\[\/img]\[\/url]/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		*/
		$text = str_replace('[rutracker.org]', "[$server_name]", $text);
		$text = str_replace('[URL=http:// СПАМ', "", $text);
		$text = str_replace('СПАМ', "", $text);
		$text = str_replace('[url=http://bb]http://bb[/url]', "", $text);
		$text = str_replace('[url=http://static]http://static[/url]', '', $text);
		$text = str_replace('[url=https://bb]http://bb[/url]', "", $text);
		$text = str_replace('[url=https://static]http://static[/url]', '', $text);
		$text = str_replace('Релиз от', "", $text);
		$text = str_replace('Релиз:', "", $text);
		//dump($text);
		$text = preg_replace('/http.*?rutracker.*?org\/forum\/viewtopic.php\?t=\d+/', "", $text);
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url]/', "", $text);
		$text = preg_replace('#\[url=http.*?nnm-club.me.*?].*?\[/url]#', "", $text);
		$text = preg_replace('/\[url=tracker.php.*?].*?\[\/url]/', "", $text);

		$text = preg_replace('#\[url=viewtopic.php.*?].*?\[/url]#', "", $text);
		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru/", $text);
		/*
						for ($i=0; $i<=20; $i++)
						{
						$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
						$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
						$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
						$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
						$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
						$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
						$text = preg_replace('#\[img]http.*?static[\s\S]*?\[/img]#', "", $text);
						}

						$pos = strpos($text, '<div class="post_body"');
						$text = substr($text, $pos);
						$pos = strpos($text, '</div><!--/post_body-->');
						$text = substr($text, 0, $pos);
						$text = preg_replace('/<div class="post_body" id=".*?">/', '', $text);



						$text = preg_replace('#<img class="smile" src=".*?" alt="" />#', '', $text);
						$text = preg_replace('#<var class="postImg" title="http://static.rutracker.org/.*?>#si', '', $text);
						$text = preg_replace('#<a href="http.*?tracker.org.*?".*?>.*?</a>#', '', $text);
						$text = preg_replace('#<a href="http.*?nnm-club.me.*?".*?>.*?</a>#', '', $text);

							$text = preg_replace('#<var class="postImg" title=".*?kinopoisk.ru/rating/(.*?).gif">.*?</var></a>#si', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
							$text = preg_replace('#<var class="postImg" title=".*?rating.kinopoisk.ru/(.*?).gif">.*?</var></a>#si', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);

							$text = preg_replace('#\n<h3 class="sp-title">.*?</h3>#', '', $text);

						$text = str_replace('<div class="q-wrap">', '', $text);
						$text = str_replace('<div class="sp-wrap">', '', $text);
						$text = str_replace('<div class="c-wrap">', '', $text);
						$text = preg_replace('#<a name=".*?"></a>#', '', $text);
						$text = str_replace('?ref_=nv_sr_1', '', $text);
						$text = preg_replace('/<a href="profile.php.*?" class="postLink">([\s\S]*?)<\/a>/', '$1', $text);
						$text = preg_replace('/<a href="viewtopic.php.*?" class="postLink">[\s\S]*?<\/a>/', '', $text);

						$text = preg_replace('#<a class="postLink-name" href=".*?">([\s\S]*?)</a>#', '$1', $text);
						$text = preg_replace('#<ol class="post-ul">([\s\S]*?)</ol>#', '$1', $text);
						//$text = preg_replace('#<hr class=".*?">([\s\S]*?)</hr>#', '$1', $text);

						$text = str_replace('<span class="post-hr">-</span>', "\n[hr]\n", $text);
						$text = preg_replace('#<pre class="post-pre">([\s\S]*?)</pre>#', '[pre]$1[/pre]', $text);

						$text = preg_replace('#<div style="margin-.*?">([\s\S]*?)</div>#', '$1', $text);
						$text = preg_replace('/<var class="postImg" title="([^<]*?)">&#10;<(?=\/)\/var>/', '[img]$1[/img]', $text);
						$text = preg_replace('/<var class="postImg postImgAligned img-(.*?)" title="(.*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);

						$text = preg_replace('/<span class="post-br".*?span>/', '[hr]', $text);
						$text = str_replace('<br>', "\n", $text);
						$text = preg_replace('/<hr class="post-hr">/', "\n[hr]", $text);

						$text = preg_replace('/<ul type="(.*?)">/', '[list=$1]', $text);
						$text = str_replace('<ul>', '[list]', $text);
						$text = str_replace('</ul>', '[/list]', $text);
						$text = str_replace('<li>', "\n[*]", $text);
						$text = str_replace('</li>', '', $text);

						$text = str_replace('<pre>', '[pre]', $text);
						$text = str_replace('</pre>', '[/pre]', $text);

						for ($i=0; $i<=20; $i++)
						{
							$text = preg_replace('/<span class="post-b">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
							$text = preg_replace('/<span class="post-u">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
							$text = preg_replace('/<span class="post-i">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
							$text = preg_replace('/<span class="post-s">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);

							$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
							$text = preg_replace('/<span class="post-font-([^<]*?)">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
							$text = preg_replace('#<a href="http://www.imdb.com/title/(.*?)/" class=".*?">.*?</a>#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);

							$text = preg_replace('#<a href="http.*?youtube.com/watch?v=(.*?)" class="postLink">.*?</a>#', "[youtube]http://www.youtube.com/watch?v=$1[/youtube]", $text);

							$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
							$text = preg_replace('/<span class="p-color" style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);


							$text = preg_replace('/<a href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
							 $text = preg_replace('/<div class="sp-head folded">([^<]*?)<\/div>([^<]*?)<(?=\/)\/div>([\s\S]*?)<div class="sp-body">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
							 $text = preg_replace('/<div class="sp-head folded">([^<]*?)<\/div>([^<]*?)<(?=\/)\/div>([\s\S]*?)<div class="sp-body">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
							 $text = preg_replace('/<div class="sp-head folded">([^<]*?)<\/div>([^<]*?)<(?=\/)\/div>([\s\S]*?)<div class="sp-body">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
							 $text = preg_replace('/<div class="sp-head folded">([^<]*?)<\/div>([^<]*?)<(?=\/)\/div>([\s\S]*?)<div class="sp-body">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
							 $text = preg_replace('/<div class="sp-head folded">([^<]*?)<\/div>([^<]*?)<(?=\/)\/div>([\s\S]*?)<div class="sp-body">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
							$text = preg_replace('/<div class="q-head">([^<]*?)<\/div>([^<]*?)<(?=\/)\/div>([\s\S]*?)<div class="q">([^<]*?)<(?=\/)\/div>([^<]*?)<([^<]*?)\/div>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
							$text = preg_replace('/<div class="q-head">([^<]*?)<\/div>([^<]*?)<(?=\/)\/div>([\s\S]*?)<div class="q">([^<]*?)<(?=\/)\/div>([^<]*?)<([^<]*?)\/div>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
							$text = preg_replace('/<div class="q-head">([^<]*?)<\/div>([^<]*?)<(?=\/)\/div>([\s\S]*?)<div class="q">([^<]*?)<(?=\/)\/div>([^<]*?)<([^<]*?)\/div>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
							$text = preg_replace('/<div class="q-head">(.*?)<\/div>([\s\S]*?)<([^<]*?)\/div>([^<]*?)<([^<]*?)\/div>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
							$text = preg_replace('/<div class="q-head">(.*?)<\/div>([\s\S]*?)<([^<]*?)\/div>([^<]*?)<([^<]*?)\/div>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
							$text = preg_replace('/<div class="q-head">(.*?)<\/div>([\s\S]*?)<([^<]*?)\/div>([^<]*?)<([^<]*?)\/div>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
							$text = preg_replace('/<div class="c-head">[\s\S]*?<div class="c-body">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[code]\n\\1\n[/code]", $text);
							$text = preg_replace('/<div style="text-align:([^<]*?)">([^<]*?)<(?=\/)\/div>/', '<div>[align=$1]$2[/align]</div>', $text);

						}

						$text = preg_replace('/<div class="sp-head folded">(.*?)<\/div>([\s\S]*?)<([^<]*?)\/div>([^<]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
						$text = preg_replace('/<div class="sp-head folded">(.*?)<\/div>([\s\S]*?)<([^<]*?)\/div>([^<]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
						$text = preg_replace('/<div class="sp-head folded">(.*?)<\/div>([\s\S]*?)<([^<]*?)\/div>([^<]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
						$text = preg_replace('/<div class="sp-head folded">(.*?)<\/div>([\s\S]*?)<([^<]*?)\/div>([^<]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
						$text = preg_replace('/<div class="sp-head folded">(.*?)<\/div>([\s\S]*?)<([^<]*?)\/div>([^<]*?)<([^<]*?)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);

						$text = preg_replace('#\[url=viewtopic.php.*?].*?\[\/url\]#', "", $text);
						$text = preg_replace('#\[url=http.*?nnm-club.me.*?].*?\[\/url\]#', "", $text);

						$text = trim(strip_tags($text));
						$text = str_replace('[rutracker.org]', "[$server_name]", $text);
						$text = str_replace('[URL=http:// СПАМ', "", $text);
						$text = str_replace('СПАМ', "", $text);
						$text = str_replace('[url=http://bb]http://bb[/url]', "", $text);
						$text = str_replace('[url=http://static]http://static[/url]', '', $text);
						$text = str_replace('[url=https://bb]http://bb[/url]', "", $text);
						$text = str_replace('[url=https://static]http://static[/url]', '', $text);
						$text = str_replace('&#235;', "ë", $text);
						$text = str_replace('&#255;', "ÿ", $text);
						$text = str_replace('&#233;', "é", $text);
						$text = str_replace('&#246;', "ö", $text);
						$text = str_replace('&#252;', "ü", $text);
						$text = str_replace('&#228;', "ä", $text);
						$text = str_replace('&#244;', 'ô', $text);
						$text = str_replace('&#231;', 'ç', $text);
						$text = str_replace('&#201;', 'é', $text);
						$text = str_replace('&#225;', 'á', $text);
						$text = str_replace('&#189;', '½', $text);
						$text = str_replace('&#58;', ':', $text);
						$text = str_replace('&#039;', "'", $text);
						$text = str_replace('&#41;', ")", $text);
						$text = str_replace('&#40;', "(", $text);
						$text = str_replace('&#amp;', " ", $text);
						$text = str_replace('&#10;', "", $text);
						$text = str_replace('&nbsp;', ' ', $text);
						$text = str_replace('Релиз от', "", $text);
						$text = str_replace('Релиз:', "", $text);
						$text = str_replace('&gt;', '>', $text);
						$text = str_replace('&#238;', "î", $text);
						$text = str_replace('&lt;', '<', $text);
						$text = str_replace('&#229;', 'å', $text);
						$text = str_replace('&quot;', '"', $text);
						$text = html_entity_decode($text);
						*/
	}

	// Вставка плеера
	insert_video_player($text);

	return $text;
}

function rutor($text, $mode = false)
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all("#<h1>([\s\S]*?)</h1>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		$text = preg_replace("/(FREEISLAND|HQCLUB|HQ-ViDEO|HELLYWOOD|ExKinoRay|NewStudio|LostFilm|RiperAM|Generalfilm|Files-x|NovaLan|Scarabey|New-Team|HD-NET|MediaClub|Baibako|CINEMANIA|Rulya74|RG WazZzuP|Ash61|egoleshik|Т-Хzona|TORRENT - BAGIRA|F-Torrents|2LT_FS|Bagira|Pshichko66|Занавес|msltel|Leo.pard|Точка Zрения|BenderBEST|PskovLine|HDReactor|Temperest|Element-Team|BT-Club|Filmoff CLUB|HD Club|HDCLUB|potroks|fox-torrents|HYPERHD|GORESEWAGE|NoLimits-Team|New Team|FireBit-Films|NNNB|New-team|Youtracker|marcury|Neofilm|Filmrus|Deadmauvlad|Torrent-Xzona|Brazzass|Кинорадиомагия|Assassin&#039;s Creed|GOLDBOY|ClubTorrent|AndreSweet|TORRENT-45|0ptimus|Torrange|Sanjar &amp; NeoJet|Leonardo|BTT-TEAM и Anything-group|BTT-TEAM|Anything-group|Gersuzu|Xixidok|PEERATES|ivandubskoj|R. G. Jolly Roger|Fredd Kruger|Киномагия|RG MixTorrent|RusTorents|Тorrent-Хzona|R.G. Mega Best|Gold Cartoon KINOREAKTOR (Sheikn)|ImperiaFilm|RG Jolly Roger|Sheikn|R.G. Mobile-Men|KinoRay &amp; Sheikn|HitWay|mcdangerous|Тorren|Stranik 2.0|Romych|R.G. AVI|Lebanon|Big111|Dizell|СИНЕМА-ГРУПП|PlanetaUA|RG Superdetki|potrokis|olegek70|bAGrat|Alekxandr48|Mao Dzedyn|Fartuna|R.G.Mega Best|DenisNN|Киномагии|UAGet|Victorious|Gold Cartoon KINOREAKTOR|KINOREAKTOR|KinoFiles|HQRips|F-Torrent|A.Star|Beeboop|Azazel|Leon-masl|Vikosol|RG Orient Extreme|R.G.TorrBy|ale x2008|Deadmauvlad|semiramida1970|Zelesk|CineLab SoundMix|Сотник|ALGORITM|E76|datynet|Дяди Лёши| leon030982|GORESEWAGE|Hot-Film|КинозалSAT|ENGINEER|CinemaClub|Zlofenix|pro100shara|FreeRutor|FreeHD|гаврила|vadi|SuperMin|GREEN TEA|Kerob|AGR - Generalfilm|R.G. DHT-Music|Витек 78|Twi7ter|KinoGadget|BitTracker|KURD28|Gears Media|KINONAVSE100|Just TeMa|OlLanDGroup|Portablius|MegaPeer|Megapeer|селезень|grab777|Twister|Twister & ExKinoRay|DrVampir|k.e.n & MegaPeer|& Хит Рус Тор|k.e.n|Batafurai Team|HEVC-CLUB|ELEKTRI4KA)/si", "Хит Рус Тор", $text);
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a href=\".*?d.rutor.info/download/([\s\S]*?)\"><img src=\".*?down.png\"> .*? ([\s\S]*?).torrent</a>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		preg_match_all("#<tr><td style=\"vertical-align:top;\">([\s\S]*?)</td></tr>#si", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];

		//Регулярка пидоров

		$text = preg_replace("/<b>Релиз от.*?<\/b>/i", "", $text);  // вырезает
		$text = preg_replace("/Автор рипа.*?<br \/>/i", "", $text); // вырезает
		$text = preg_replace("/<span.*?Релиз от.*?<\/span><img.*?\/>/i", "", $text);  // вырезает
		$text = preg_replace("/Скачать.*?<br \/>/i", "", $text); // вырезает
		$text = preg_replace("/источник.*?<br \/>/i", "", $text); // вырезает
		$text = preg_replace("/Рип от.*?<br \/>/i", "", $text); // вырезает
		$text = preg_replace("/Раздача от.*?<br \/>/i", "", $text); // вырезает
		//$text = preg_replace("/Сравнение с исходником.*?<br \/>/i", "\n", $text); // вырезает
		//$text = preg_replace("/screenshotcomparison.com.*?<br \/>/i", "\n", $text); // вырезает
		$text = preg_replace('/<center><img .*?><a href=".*?scarabey.org.*?" target="_blank">.*?<\/a><img.*?><\/center>/si', "", $text); // вырезает


		$text = preg_replace("/<b>Релиз: <\/b>/i", "", $text); // вырезает

		//$text = preg_replace('/<a href="http:\/\/rutor.*?" target="_blank">.*?<\/a>/', "", $text);
		$text = preg_replace('/<.*?><a href="http:\/\/rutor.*?" target="_blank">.*?<\/a><\/.*?>/', "", $text);

		//$text = preg_replace('/<\/td>.*?<br \/>.*?<img src="([\s\S]*?)".*?\/>/siu', '[img=right]$1[/img]', $text);

		$text = preg_replace('/<td>.*?<img src="([\s\S]*?)".*?\/>/', '[img=right]$1[/img]', $text);

		$text = preg_replace('#<a href="http.*?megapeer.*?</a>#', '', $text);
		//$text = preg_replace('#<a href="http.*?vk.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://exkinoray.tv.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://hellywood.ru/.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://hq-video.org/.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://hqclub.net/.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://exkinoray.tv.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://exkinoray.org.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://interhit.org.*?</a>#', '', $text);
		$text = preg_replace('#<img src="http://exkinoray.tv/pic/offbanner/reliz.exkinoray.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://img23.binimage.org/34/36/9a/enigmavladislav71.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://riper.am/riperam.gif.*?>#', '', $text);
		$text = preg_replace('#<a href="http://riper.am/.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://www.generalfilm.ws.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://generalfilm.ws.*?</a>#', '', $text);
		$text = preg_replace('#<img src="http://s019.radikal.ru/i603/1209/87/535dfd778010.gif".*?>#', '', $text);
		$text = preg_replace('#&amp; <img src="http://s017.radikal.ru/i412/1208/f2/bf3e5e3f51c8.gif.*?>#', ' ', $text);
		$text = preg_replace('#<img src="http://2.firepic.org/2/images/2012-03/07/tp2rxwtz8xl3.gif".*?>#', '', $text);
		$text = preg_replace('#<img src="http://www.agrmusic.org/.*?>#', '', $text);
		$text = preg_replace('#<a href="http://tracker.nova-lan.ru/.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://nick-name.ru/.*?</a>#', ' ', $text);
		$text = preg_replace('#<img src="http://i50.fastpic.ru/big/2013/0803/58/6cd4a3d8ac226de81209cee3369dc458.gif.*?>#', ' ', $text);
		$text = preg_replace('#<img src="http://i92.fastpic.ru/big/2017/0727/ee/d5deb5b73669b4c45974c6b5d6c309ee.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://megarelizer.ru/.*?>#', '', $text);
		$text = preg_replace('#<img src="http://i.imgur.com/1qnKU.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://www.freeisland.org/.*?>#', '', $text);
		//$text = preg_replace('#<a href="http://scarabey.org/.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://new-team.org.*?</a>#', '', $text);
		$text = preg_replace('#<img src="http://i57.fastpic.ru/big/2013/1128/8e/0f1951eab7c36987190091c1d058ae8e.jpg.*?>#', ' ', $text);
		$text = preg_replace('#<img src="http://s019.radikal.ru/i622/1306/32/980d60bc4416.gif".*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://www.hd-net.org.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://mediaclub.tv.*?</a>#', '', $text);
		$text = preg_replace('#<img src="http://i056.radikal.ru/1402/31/35cc79d56ba1.gif.*?>#', ' ', $text);
		$text = preg_replace('#<img src="http://lostpic.net/orig_images/9/6/9/969f804e8322f6fb6bbf24462c4f3bad.png".*?>#', '', $text);
		$text = preg_replace('#<img src="http://cinemania.cc/pic/groups/CINEMANIA.png".*?>#', '', $text);
		$text = preg_replace('#<a href="http://cinemania.cc/.*?</a>#', '', $text);
		$text = preg_replace('#<img src="<img src="http://s005.radikal.ru/i212/1309/51/23eede0fecd5.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s019.radikal.ru/i624/1309/2d/7c5758387429.gif.*?>#', '', $text);
		$text = preg_replace('#<a href="http://rutor.info.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://zerkalo-rutor.org.*?</a>#', '', $text);
		$text = preg_replace('#<img src="http://i59.fastpic.ru/big/2013/1129/34/0fc4a1022f44fd99fd69acbb5d5d1d34.jpeg.*?>#', '', $text);
		$text = preg_replace('#<img src="http://fotohost.kz/images/2014/03/02/JyoXZ.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s020.radikal.ru/i717/1309/b9/e1cf911f8341.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s020.radikal.ru/i718/1311/c5/6a57c000d933.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s57.radikal.ru/i157/1402/9c/352c1ea45daf.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://i069.radikal.ru/1402/dd/a538fe599270.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s017.radikal.ru/i404/1309/60/16fa77685833.gif" /> <img src="http://s59.radikal.ru/i163/1308/21/6e04962f4ee1.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://i056.radikal.ru/1301/d3/79bb1765a159.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://i59.fastpic.ru/big/2013/1129/34/0fc4a1022f44fd99fd69acbb5d5d1d34.jpeg.*?>#', '', $text);
		$text = preg_replace('#<img src="http://filmrus.net/pic/groups/FilmRus.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://hdreactor.org/freehd.png.*?>#', '', $text);
		$text = preg_replace('#<img src="http://i33.fastpic.ru/big/2012/0324/17/b3009b8b3e6c80c43db9561cab87ec17.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s018.radikal.ru/i520/1307/91/9bb3f0c192f0.png.*?>#', '', $text);
		$text = preg_replace('#<img src="http://www.filmrus.net/pic/groups/FfClub.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://2.firepic.org/2/images/2012-04/04/8hvkmj1lk60e.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://files-x.com/.*?>#', '', $text);
		$text = preg_replace('#<img src="http://firepic.org/images/2011-09/pe9tr9qjw85d24w4zizfmem17.jpg.*?>#', '', $text);
		$text = preg_replace('#<img src="http://2.firepic.org/2/images/2011-12/26/o2lz4uqlwnt6.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://2.firepic.org/2/images/2011-11/07/5u8270klj72q.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://rustorents.com/pic/knopka.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s.rutor.org/t/button1.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s.rutor.info/t/button1.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s019.radikal.ru/i627/1302/41/7689dc958955.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://s010.radikal.ru/i313/1412/59/8882057187ec.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://www.imageup.ru/img286/1291032/0ptimus.gif.*?>#', '', $text);
		$text = preg_replace('#<a href="http://www.rutor.info/.*?</a>#', '', $text);
		$text = preg_replace('#<img src="http://s014.radikal.ru/i328/1410/76/bbeb05031521.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://lostpic.net/orig_images/c/2/3/c23b68bf7f9713c5bc6bca6a3c6c7f44.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://i66.fastpic.ru/big/2015/0113/73/e08879f9d7f777b8bbdf2315f7995a73.jpeg.*?>#', '', $text);
		$text = preg_replace('#<img src="http://www.hq-video.org/images/hq_88_31.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://i42.fastpic.ru/big/2012/0808/51/5a228c567a256ef93291927224da4d51.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://2.firepic.org/2/images/2015-05/30/9is8sqoz1bom.gif.*?>#', '', $text);
		$text = preg_replace('#<a href="http://open-tor.org/.*?</a>#', '', $text);
		$text = preg_replace('#<img src="http://interhit.org/pic/pics.gif.*?>#', '', $text);
		$text = preg_replace('#<a href="http://tor-ru.net.*?</a>#', '', $text);
		$text = preg_replace('#<a href="http://files-x.rip.*?</a>#', '', $text);
		$text = preg_replace('#<img src="http://2.firepic.org/2/images/2013-09/02/0h3zu24zoutd.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://i58.fastpic.ru/big/2015/0506/2d/36f03ef0ca5684e2fbf0a89b2413f52d.gif.*?>#', '', $text);
		$text = preg_replace('#<img src="http://lostpic.net/orig_images/2/8/7/287f8a16aa58f4522ee27dbd7096fd32.gif.*?>#', '', $text);

		$text = preg_replace('#<a href=".*?changecopyright.ru" target="_blank">.*?</a>#', '', $text);
		$text = preg_replace('#<a href=".*?scarabey.*?</a>#', '', $text);
		$text = preg_replace('#<a href=".*?zarunet.org/" target="_blank">.*?</a>#', '', $text);
		//Регулярка пидоров конец
		$text = preg_replace('/<td><br.*?><center><img src="([\s\S]*?)" \/><\/center>/siu', '<center>[img]$1[/img]</center>', $text);
		$text = preg_replace('/<br.*?>/', "", $text);
		$text = preg_replace('/<a href="\/tag\/.*?" target="_blank">([\s\S]*?)<\/a>/', '$1', $text);

		$text = preg_replace('/<a href="([\s\S]*?)" target="_blank">([\s\S]*?)<\/a>/', '[url=$1]$2[/url]', $text);
		$text = preg_replace('/<img src="(.*?)" style="float:(.*?);" \/>/', '[img=$2]$1[/img]', $text);
		$text = preg_replace('/<img src="([\s\S]*?)" \/>/', '[img]$1[/img]', $text);

		$text = str_replace('<center>', '[align=center]', $text);
		$text = str_replace('</center>', '[/align]', $text);
		$text = str_replace('<hr />', '[hr]', $text);

		$text = str_replace('&#039;', "'", $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<b>([^<]*?)<(?=\/)\/b>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<u>([^<]*?)<(?=\/)\/u>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<i>([^<]*?)<(?=\/)\/i>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<s>([^<]*?)<(?=\/)\/s>/', '[s]$1[/s]', $text);
			$text = preg_replace('/<font size="([^<]*?)">([^<]*?)<(?=\/)\/font>/', "[size=2\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="color:([^<]*?);">([\s\S]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span style="font-family:([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[font="$1"]$2[/font]', $text);
			$text = preg_replace('/<div class="hidewrap"><div class="hidehead" onclick="hideshow.*?"><span style="color.*?">([\s\S]*?)<\/span><\/div><div class="hidebody"><\/div><textarea class="hidearea">([\s\S]*?)<\/textarea><\/div>/', "[spoiler=\"\\1\"]\\2[/spoiler]", $text);
			/*

							$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
							$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
							$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
							$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
							$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
							$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru/", $text);
			$text = preg_replace('/<div class="hidewrap"><div class="hidehead" onclick="hideshow.*?">([\s\S]*?)<\/div><div class="hidebody"><\/div><textarea class="hidearea">([\s\S]*?)<\/textarea><\/div>/', "[spoiler=\"\\1\"]\\2[/spoiler]", $text);
		}
		//$text = preg_replace('/<td>[\s\S]*?Мы.*?<a href="http.*?vk.com\/scarabey_new_team" target="_blank"><img.*?<br \/>/siu', "", $text);

		$text = str_replace('<a href="http://rublacklist.net/" target="_blank"><img src="http://rublacklist.net/media/2015/08/RKS-468_2.jpg"></a><br />', '', $text);

		$text = str_replace('<a href="http://openrunet.org/" target="_blank"><img src="http://rublacklist.net/media/2015/12/openrunet_h_long_bl_468.jpg"></a><br />', '', $text);
		$text = str_replace('<a href="http://zarunet.org/" target="_blank"><img src="http://rublacklist.net/media/2015/12/zarunet_h_1_468.png"></a><br />', '', $text);

		$text = preg_replace('#\[url=http.*?imdb.com/title/(.*?)/].*?\[\/url\]#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);

		// Вставка плеера
		insert_video_player($text);

		$text = strip_tags(html_entity_decode($text));
	}

	return $text;
}

function nnmclub($text, $mode = '')
{
	global $bb_cfg;

	$server_name = $bb_cfg['server_name'];
	$sitename = $bb_cfg['sitename'];
	if ($mode == 'title') {
		preg_match_all("#<a class=\"maintitle\" href=\"viewtopic.php\?t=.*?\">(.*?)</a>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all('#<td colspan="3" class="gen".*?><b>([\s\S]*?).torrent</b></td>.*?<a href="download.php\?id=([\d]+)" rel="nofollow">.*?</a>#s', $text, $source, PREG_SET_ORDER);
		$text = $source[0];
	} else {
		if (preg_match_all("#.*?/reply-locked.gif#si", $text, $source, PREG_SET_ORDER)) {

			$pos = strpos($text, '<td colspan="2"><div class="postbody"');
			$text = substr($text, $pos);
			$pos = strpos($text, '<tr><td colspan="2">');
			$text = substr($text, 0, $pos);

			$text = str_replace('<span class="postbody">', '', $text);
			$text = str_replace('<!--/spoiler-body-->', '', $text);
			$text = str_replace('<!--/spoiler-wrap-->', '', $text);
			//$text = str_replace('<div class="spoiler-wrap">', '', $text);
			$text = str_replace('<div class="clear"></div>', '', $text);

			$text = str_replace('<div class="hide spoiler-body inited" title="" style="display: block;">', '', $text);
			$text = str_replace('<div class="hide spoiler-wrap">', '', $text);
			$text = preg_replace('/<div class="spoiler-wrap.*?>/', '', $text);

			$text = str_replace('hide spoiler-body', 'spoiler-body', $text);
			$text = preg_replace('/<img src=".*?" alt=".*?" border="0"\/>/', '', $text);
			$text = str_replace('<hr />', "[hr]", $text);

			$text = preg_replace('/<img style="float: (.*?);.*? src="(.*?)" alt="Image" title="Image" border="0" \/>/', "[img=\\1]\\2[/img]\n", $text);

			$text = preg_replace('/<var class="postImg postImgAligned img-(.*?)" title="(.*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
			$text = str_replace('<span class="post-br"></span>', "\n[br]\n", $text);

			$text = str_replace('<br />', "\n\n", $text);

			$text = str_replace('<ul>', '[list]', $text);
			$text = str_replace('</ul>', '[/list]', $text);
			$text = str_replace('<ol type="">', '[list]', $text);
			$text = str_replace('</ol>', '[/list]', $text);
			$text = str_replace('<div class="hide spoiler-body inited" title="" style="display: block;">', '', $text);
			$text = str_replace('<li>', "\n[*]", $text);
			$text = str_replace('</li>', '', $text);
			//$text = str_replace('<div class="hide spoiler-wrap">', '', $text);
			$text = str_replace('<center>', "[align=center]", $text);
			$text = str_replace('</center>', "[/align]", $text);

			$text = preg_replace('/<table width="90%" cellspacing="1" cellpadding="3" class="qt".*? class="code">(.*?)<\/td>.*?<\/table>/si', '[code]$1[/code]', $text);

			$text = str_replace('<code>', '', $text);
			$text = str_replace('</code>', '', $text);

			$text = str_replace('&#228;', "ä", $text);
			$text = str_replace('&#215;', '×', $text);
			$text = str_replace('&#039;', "'", $text);
			$text = str_replace('&nbsp;', ' ', $text);
			$text = str_replace('&gt;', '>', $text);
			$text = str_replace('&lt;', '<', $text);

			$text = str_replace('<!--[if lte IE 9]>', '', $text);
			$text = str_replace('<![endif]-->', '', $text);
			$text = str_replace('<![if !IE]>', '', $text);
			$text = str_replace('<![endif]>', '', $text);
			$text = str_replace('http://assets.nnm-club.ws/forum/image.php?link=', '', $text);
			$text = str_replace('http://nnmassets.cf/forum/image.php?link=', '', $text);
			$text = str_replace('https://nnmclub.ch/forum/image.php?link=', '', $text);

			$text = str_replace('http://assets.ipv6.nnm-club.ws/forum/image.php?link=', '', $text);
			$text = preg_replace('/hs.expand(this,{slideshowGroup:.*?})"/', '', $text);
			$text = preg_replace('/<span class="imdbRatingPlugin".*?>/', '', $text);
			$text = str_replace('https://assets.nnm-club.ws/forum/images/channel/sample_light_nnm.png', "https://$server_name/data/pictures/2/24.jpg", $text);
			$text = str_replace('https://href.li/?', '', $text);
			$text = str_replace('http://nnmclub.ch/forum/image.php?link=', '', $text);

			$text = str_replace('<tr>', '', $text);
			$text = str_replace('</tr>', '', $text);
			$text = str_replace('<td>', '', $text);
			$text = str_replace('</td>', '', $text);
			$text = preg_replace('/<a href=\"\/forum\/.*?\" rel="nofollow.*?" class="postLink">(.*?)<\/a>/', '$1', $text);
			$text = preg_replace('/<table.*?>/', '', $text);
			$text = preg_replace('/<tr.*?>/', '', $text);
			$text = str_replace('</tr>', '', $text);
			$text = preg_replace('/<td.*?>/', '', $text);
			$text = str_replace('</td>', '', $text);
			$text = str_replace('NNMClub', "$sitename", $text);
			$text = str_replace('</table>', '', $text);
			$text = str_replace('<noindex>', '', $text);
			$text = str_replace('</noindex>', '', $text);
			$text = str_replace('?ref_=plg_rt_1', '', $text);
			$text = preg_replace('/<object .*? value="(.*?)">.*?<\/object>/si', '[youtube]$1[/youtube]', $text);
			$text = preg_replace('/<var class="postImg" title="(.*?)">&#10;<\/var>/', '[img]$1[/img]', $text);
			$text = preg_replace('/<img src="(.*?)" alt=".*?" border="0" \/>/', '[img]$1[/img]', $text);
			$text = preg_replace('/<a href="#" onclick=".*?">.*?<\/a>/', '', $text);
			$text = preg_replace('#<a href="(.*?)"><img src=".*?" class="ytlite tit-y" title=".*?" alt=".*?"></a>#', '[youtube]$1[/youtube]', $text);

			for ($i = 0; $i <= 20; $i++) {
				$text = preg_replace('/<span class="text-glow" style="text-shadow:0px 0px 5px .*?;">([^<]*?)<(?=\/)\/span>/', '$1', $text);
				$text = preg_replace('/<span style="text-shadow:0px 0px 10px .*?;">([^<]*?)<(?=\/)\/span>/', '$1', $text);
				$text = preg_replace('/<span style="text-shadow:1px 1px 2px lightgrey;" class="text-shadow"><span style="text-shadow:3px 3px 3px lightgrey;">([^<]*?)<(?=\/)\/span><\/span>/', '$1', $text);
				$text = preg_replace('/<span style="font-weight: bold">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
				$text = preg_replace('/<span style="text-decoration: underline">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
				$text = preg_replace('/<span style="font-style: italic">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
				$text = preg_replace('/<span style="text-decoration: line-through">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
				$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
				$text = preg_replace('/<span style="font-family: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
				$text = preg_replace('#<a href=".*?&amp;w=title".*?class="postLink">.*?Все одноименные релизы в Клубе.*?</a>#', '', $text);
				$text = preg_replace('/<span style="text-align: ([^<]*?); display: block;">([\s\S]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
				$text = preg_replace('/<span style="color: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);

				$text = preg_replace('/<a href="(.*?)" style.*?class="highslide" .*?rel="nofollow.*?>([\s\S]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);

				$text = preg_replace('/<a href="(.*?)".*?>([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
				$text = preg_replace('/<pre>([^<]*?)<\/pre>/', '[pre]$1[/pre]', $text);
				$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru/", $text);
				$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
				$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
				$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
				$text = preg_replace('#\[url=http.*?imdb.com/title/(.*?)/].*?\[\/url\]#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
				$text = preg_replace('#<a href="/?q=.*?w=title".*?>#', '', $text);

				$text = preg_replace('/<div class="spoiler-body" title="([^<]*?)">([\s\S]*?)<(?=\/)\/div><(?=\/)\/div>/', '[spoiler="$1"]$2[/spoiler]', $text);
				$text = preg_replace('/<div class="hide spoiler-wrap">.*?<div class="spoiler-body">([\s\S]*?)<(?=\/)\/div><(?=\/)\/div>/', '[spoiler]$1[/spoiler]', $text);

				$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
				$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
				$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
				$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
				$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
				$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
				$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);

			}

			$text = preg_replace('#\[url=mailto.*?].*?\[\/url\]#', "$1", $text);

			$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);
			//$text = str_replace('[url=/forum', '[url=http://nnm-club.me/forum', $text);
			$text = strip_tags(html_entity_decode($text));
		} else {
			preg_match_all("#<a href=\"posting.php\?mode=quote&amp;p=(.*?)\" rel=\"nofollow\"><img src=\".*?icon_quote.gif\".*?border=\"0\" class=\"pims\"></a>#", $text, $id, PREG_SET_ORDER);
			$post_id = $id[0][1];


			$curl = new \Dinke\CurlHttpClient;

			//use proxy
			$curl->setProxy('38.170.252.172:9527');
			//use proxy auth
			$curl->setProxyAuth('cZbZMH:6qFmYC');
			$url = "https://nnmclub.to/forum/posting.php?mode=quote&p=$post_id";
			$curl->setUserAgent("Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1");
			$curl->storeCookies(COOKIES_PARS_DIR . '/nnm_cookie.txt');
			$source = $curl->fetchUrl($url);

			/*
							$url = "https://nnmclub.to/forum/posting.php?mode=quote&p=$post_id";
							$source = openUrlCloudflare($url);
			*/
			$source = iconv('windows-1251', 'UTF-8', $source);
			$text = $source;
			preg_match_all("#<textarea.*?\">\[\quote=\".*?\";p=\".*?\"\]([\s\S]*?)\[/quote\]</textarea>#", $text, $source, PREG_SET_ORDER);
			$text = $source[0][1];
			//var_dump($text );


			if (!$text) {
				meta_refresh('release.php', '8');
				bb_die('Куки не найдены, попробуйте ещё раз, со второго раза точно получится');
			}

			$text = str_replace('NNMClub', "$sitename", $text);
			$text = str_replace('[poster=', '[img=', $text);
			$text = str_replace('[/poster]', '[/img]', $text);
			$text = str_replace('[poster]', '[img]', $text);
			$text = str_replace('[center]', "[align=center]", $text);
			$text = str_replace('[list=]', '[list]', $text);
			$text = str_replace('[/center]', "[/align]", $text);
			$text = str_replace('?ref_=plg_rt_1', '', $text);
			$text = str_replace('[table]', "", $text);
			$text = str_replace('[/table]', "", $text);
			$text = str_replace('[box]', "", $text);
			$text = str_replace('[/box]', "", $text);
			$text = str_replace('[cut]', "", $text);
			$text = str_replace('[simg]', "[img]", $text);
			$text = str_replace('[/simg]', "[/img]", $text);
			$text = str_replace('[yt]', "[youtube]", $text);
			$text = str_replace('[/yt]', "[/youtube]", $text);
			$text = preg_replace('/\[spoiler=([\s\S]*?)\]/', '[spoiler="$1"]', $text);
			$text = preg_replace('/\[url=https:\/\/nnmclub.to\/\?q=.*?=text\]([\s\S]*?)\[\/url]/', '$1', $text);
			$text = preg_replace('/\[acronym=.*?\]([\s\S]*?)\[\/acronym]/', '$1', $text);

			$text = preg_replace('/\[url=http.*?nnm.*?\/forum\/viewtopic.php.*?\].*?\[\/url]/', '', $text);

			$text = preg_replace('/\[hide=([\s\S]*?)\]/', '[spoiler="$1"]', $text);
			$text = str_replace('[/hide]', '[/spoiler]', $text);
			$text = str_replace('[brc]', "", $text);
			$text = preg_replace('/\[imdb\]tt([\d]+)\[\/imdb\]/', '[imdb]https://www.imdb.com/title/tt$1[/imdb]', $text);
			$text = preg_replace('/\[kp\]([\d]+)\[\/kp\]/', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);

			$text = str_replace('http://assets.nnm-club.ws/forum/image.php?link=', '', $text);
			$text = str_replace('http://nnmassets.cf/forum/image.php?link=', '', $text);

			$text = str_replace('http://assets.ipv6.nnm-club.ws/forum/image.php?link=', '', $text);
			$text = preg_replace('/hs.expand(this,{slideshowGroup:.*?})"/', '', $text);
			$text = str_replace('forum/images/channel/sample_light_nnm.png', "https://$server_name/data/pictures/2/24.jpg", $text);
			$text = str_replace('https://href.li/?', '', $text);
			$text = str_replace('http://nnmclub.ch/forum/image.php?link=', '', $text);
			$text = preg_replace('#\[url=mailto.*?].*?\[\/url\]#', "$1", $text);
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru/", $text);
			/*
				$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
				$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
				$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
				$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
				$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
				$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
*/
		}
	}

	return $text;
}

function rustorka($text, $mode = '')
{
	global $bb_cfg;

	$server_name = $bb_cfg['server_name'];
	$sitename = $bb_cfg['sitename'];

	if ($mode == 'title') {
		preg_match_all("#<h1 class=\"bigtitle\"><a href=\".*?\">([\s\S]*?)</a></h1>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		$text = str_replace('<wbr>', '', $text);
	} elseif ($mode == 'torrent') {
		preg_match_all("#<th colspan=\"3\" class=\"genmed\">(.*?).torrent</th>[\s\S]*?<a href=\"download.php\?id=(.*?)\" #", $text, $source, PREG_SET_ORDER);
		$text = $source[0];
	} else {
		//preg_match_all ("#<div class=\"post_body\">([\s\S]*?)<div class=\"spacer_8\"></div>#si", $text, $source, PREG_SET_ORDER);
		//$text = $source[0][1];

		$pos = strpos($text, '<div class="post_body"');
		$text = substr($text, $pos);
		$pos = strpos($text, '<div class="spacer_8"></div>');
		$text = substr($text, 0, $pos);
		$text = preg_replace('/<div class="post_body">/', '', $text);

		$text = str_replace('<wbr>', '', $text);
		$text = preg_replace('/<a href="http:\/\/rustorka.com\/forum\/search.*?nm=.*?" class="postLink">(.*?)<\/a>/', '$1', $text);
		$text = str_replace('/go.php?url=', '', $text);


		$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
		$text = str_replace('<div class="clear"></div>', '', $text);
		$text = preg_replace('/<!--\/.*?-->/', '', $text);
		$text = str_replace('<div class="spoiler-wrap">', '', $text);
		$text = str_replace('<div class="code_wrap">', '', $text);
		$text = str_replace('<div class="spoiler-body"></span></span></span></span>', '<span class="spoiler-body1">', $text);

		$text = str_replace('<hr />', "\n[hr]\n", $text);
		$text = preg_replace('/<div class="postImg-wrap" style=".*?" align="center"><img src="([^<]*?)" id="postImgAligned" class="postImg" alt="pic" \/><\/div>/', "[align=center][img]\\1[/img][/align]\n", $text);
		$text = preg_replace('/<div class="postImg-wrap" style=".*?" align="([^<]*?)"><img src="([^<]*?)" id="postImgAligned" class="postImg" alt="pic" \/><\/div>/', "[img=\\1]\\2[/img]\n", $text);
		$text = preg_replace('/<img src="([^<]*?)" id="postImg" class="postImg" align="absmiddle" hspace="0" vspace="4" alt="pic" \/>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<a href="([^<]*?)" rel=".*?" class="zoom"><img src="[^<]*?".*?<(?=\/)\/a>/si', "[th]$1[/th]", $text);
		$text = preg_replace('/<a href="([^<]*?)" target="_blank" \/><img.*?src="kinopoisk.php\?id=.*?".*?><\/a>/', "[kp]$1[/kp]", $text);

		$text = preg_replace('/<iframe width=".*?" height=".*?" src=\"([^<]*?)\" frameborder="0" allowfullscreen><\/iframe>/', '[align=center][youtube]$1[/youtube][/align]', $text);
		$text = str_replace('<b>', "[b]", $text);
		$text = str_replace('</b>', "[/b]", $text);

		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('</li>', '', $text);
		$text = str_replace('<br />', "\n", $text);
		$text = str_replace('<br clear="all" />', "\n[br]\n", $text);
		$text = str_replace('<div></div>', "\n", $text);
		$text = preg_replace('/<div class="code_head">.*?<script type="text\/javascript">.*?<\/script>.*?<\/div>/', "", $text);

		$text = preg_replace_callback('/<a href="\/cdn-cgi\/l\/email-protection" class="__cf_email__" data-cfemail="([a-z\d]*)">([^<]*)<\/a>/s', function ($matches) {
			return decodeEmailProtection($matches[1]);
		}, $text);


		$text = str_replace('<div', '<span', $text);
		$text = str_replace('</div>', '</span>', $text);
		$text = str_replace('<a', '<span', $text);
		$text = str_replace('</a>', '</span>', $text);
		$text = str_replace('&#039;', "'", $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);

		$text = preg_replace_callback('/<span href="http.*?rustorka.com\/forum\/viewtopic.*?" class="postLink">(.*?)<\/span>/', function ($v) use ($server_name) {
			$text_data = $v[1];
			$text_url = strip_tags($text_data);
			return "[url=https://$server_name/tracker.php?" . http_build_query(array('nm' => $text_url)) . ']' . $text_data . '[/url]';
		},
			$text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<span style="font-weight: bold;">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<span style="text-decoration: underline;">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<span style="text-shadow:  1px 1px 3px [^<]*?">([^<]*?)<(?=\/)\/span>/', '[sh]$1[/sh]', $text);

			$text = preg_replace('/<span style="font-style: italic;">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('#<param name="movie" value="(.*?)"></param>#', "[align=center][youtube]$1[/youtube][/align]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru/", $text);
			$text = preg_replace('/<span href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);

			$text = preg_replace('/<p class="q_head"><b>.*?<\/b><\/p>[\s\S]*?<div class="q">([^<]*?)<(?=\/)\/div><!--\/q-->/', "[quote]\n\\1\n[/quote]", $text);
			$text = preg_replace('/<p class="q_head"><b>(.*?)<\/b>.*?<\/p>[\s\S]*?<div class="q">([^<]*?)<(?=\/)\/div><!--\/q-->/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
			$text = preg_replace('/<span class="code">([\s\S]*?)<(?=\/)\/span>/', "[code]\n\\1\n[/code]", $text);
			//$text = preg_replace('/<span class="code_head">([\s\S]*?)<(?=\/)\/span>/', "[code]\n\\1\n[/code]", $text);
			$text = preg_replace('/<span class="spoiler-head folded clickable nowrap">([^<]*?)<(?=\/)\/span>.*?<span class="spoiler-body">([^<]*?)<(?=\/)\/span>([^<]*?)<(?=\/)\/span>/', "\n[spoiler=\"\\1\"]\n\\2\n[/spoiler]\n", $text);
			$text = preg_replace('/<span class="spoiler-head folded clickable nowrap">([^<]*?)<(?=\/)\/span>.*?<span class="spoiler-body1">([\s\S]*?)<(?=\/)\/span>([^<]*?)<(?=\/)\/span>([^<]*?)<(?=\/)\/span>/', "\n[spoiler=\"\\1\"]\n\\2\n[/spoiler]\n", $text);
			$text = preg_replace('/<span style="text-align: ([^<]*?);">([\s\S]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
			$text = preg_replace('#\[url=http.*?imdb.com/title/(\w+\d+)/].*?\[\/url\]#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
			$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(\d+)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
			$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(\d+)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
			$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(\d+)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
			$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(\d+)].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
			$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		}

		$text = strip_tags(html_entity_decode($text));
		//dump($text);

	}
	return $text;
}

function booktracker($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all("#<h1 class=\"maintitle\"><a href=\".*?\">([\s\S]*?)</a></h1>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		$text = str_replace('<wbr>', '', $text);
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a href=\"download.php\?id=(.*?)\" class#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		$pos = strpos($text, '<div class="post_body">');
		$text = substr($text, $pos);
		$pos = strpos($text, '<div class="spacer_8"></div>');
		$text = substr($text, 0, $pos);
		$text = str_replace('/<div class="clear"></div>/', '', $text);
		$text = str_replace('<wbr>', '', $text);
		//$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);

		/*$text = preg_replace('#http:\/\/rustorka.com\/forum\/tracker.php\?(.*?)nm=(.*?)#', 'http://hot-torr.ru/tracker.php?nm=$2', $text);
		$text = str_replace('http://rustorka.com/forum', 'http://hot-torr.ru', $text);*/


		$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
		$text = str_replace('<div class="clear"></div>', '', $text);
		$text = preg_replace('/<!--\/.*?-->/', '', $text);
		$text = str_replace('<hr />', "\n[hr]\n", $text);
		$text = preg_replace('/<var class="postImg" title="(.*?)">&#10;<\/var>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<var class="postImg postImgAligned img-(.*?)" title="(.*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
		$text = preg_replace('/<img src="(.*?)" id="postImg" class="postImg" align="absmiddle" hspace="0" vspace="4" alt="pic" \/>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<span style="text-shadow: #000 1px 1px 2px, black 0 0 1em;">(.*?)<\/span>/', '[sh]$1[/sh]', $text);
		$text = preg_replace('/<object .*? value="(.*?)".*?<\/object>/', '[youtube]$1[/youtube]', $text);
		$text = str_replace('youtube.com/v/', 'youtube.com/watch?v=', $text);
		$text = preg_replace('/<a href="([^<]*?)" target="_blank" \/>([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);

		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('</li>', '', $text);
		$text = str_replace('<br /><br />', "\n\n", $text);
		$text = str_replace('<br clear="all" />', "\n[br]\n", $text);
		$text = str_replace('<div></div>', "\n", $text);
		$text = str_replace('<div', '<span', $text);
		$text = str_replace('</div>', '</span>', $text);
		$text = str_replace('<a', '<span', $text);
		$text = str_replace('</a>', '</span>', $text);
		$text = str_replace('&#039;', "'", $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('&#10;', '<', $text);
		$text = str_replace('&quot;', '', $text);
		$text = str_replace('&#10;', "'", $text);
		$text = preg_replace('/<!--\/.*?-->/', '', $text);
		$text = str_replace('<span class="sp-wrap">', '', $text);
		$text = str_replace('<span class="post_body">', '', $text);
		//$text = preg_replace('/<span href=".*?booktracker.*?" class="postLink">.*?<\/span>/', '', $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<span style="font-weight: bold;">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<span style="text-decoration: underline;">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<span style="font-style: italic;">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('/<span style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);

			//$text = preg_replace('/<a.*?rel=".*?" class="zoom"><img src="(.*?)".*? \/>&nbsp;<\/a>/', '[th]$1[/th]', $text);

			$text = preg_replace('/<span href="([^<]*?)" target="_blank" \/>([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<p class="q_head"><b>.*?<\/b><\/p>[\s\S]*?<div class="q">([^<]*?)<(?=\/)\/div><!--\/q-->/', "[quote]\n\\1\n[/quote]", $text);
			$text = preg_replace('/<p class="q_head"><b>(.*?)<\/b>.*?<\/p>[\s\S]*?<div class="q">([^<]*?)<(?=\/)\/div><!--\/q-->/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
			$text = preg_replace('/<span class="code">([^<]*?)<(?=\/)\/span>/', "[code]\n\\1\n[/code]", $text);
			$text = preg_replace('/<span class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			$text = preg_replace('/<span class="sp-body">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[spoiler]\n\\1\n[/spoiler]", $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
			$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		}

		// Убираем пустое пространство
		//$text = trim(strip_tags($text));
		$text = html_entity_decode($text);
	}
	return $text;
}

function torrentwindows($text, $mode = false)
{
	global $bb_cfg;

	$server_name = $bb_cfg['server_name'];
	$sitename = $bb_cfg['sitename'];

	if ($mode == 'title') {
		preg_match_all('#<h1 class="flex-grow-1">([\s\S]*?)</h1>#', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		//$text = str_replace(' скачать торрент бесплатно', '', $text);
	} elseif ($mode == 'torrent') {
		preg_match_all('#<a href=".*?index.php\?do=download&id=([\d]+)" class="fdl-btn">#', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		//dump($text);
	} else {
		preg_match_all('#<div class="page__desc">([\s\S]*?)<style>#si', $text, $source, PREG_SET_ORDER);

		preg_match_all('/<div class="page__poster img-fit-cover">.*?<img src="([^<]*?)" alt=".*?" \/>/s', $text, $pic, PREG_SET_ORDER);
		$pic = $pic[0][1];
		$poster = ($pic) ? "[img=right]" . "https://torrent-wind.net$pic" . "[/img]\n" : "";

		$text = $poster . @$source[0][1];

		$text = preg_replace_callback('/<center>.*?<h4 class="page__subtitle">([\s\S]*?)<\/h4>[\s\S]*?center>/s', function ($v) {
			if ($v && is_array($v)) {
				foreach ($v as $v1)
					$v1 = str_replace('полный обзор ', '', $v1);
				$v2 = "[hr][hr]\n[align=center][size=24][color=darkblue][font=\"Consolas\"][b][sh]" . $v1 . "[/sh][/b][/font][/color][/size][/align]\n[hr][hr]\n";
			}
			{
				return $v2;
			}
		},
			$text);

		$text = preg_replace('/<div class="page__text full-text"><div align=".*?"><!--TBegin:.*?--><a href.*?><\/a><!--TEnd--><\/div>/s', '', $text);
		$text = preg_replace('/\[spoiler=([^"]*?)]/', '[spoiler="$1"]', $text);

		$text = preg_replace("/<br class=\"clearfix\" \/>\n\n/", '', $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('<div', '<span', $text);
		$text = str_replace('https://href.li/?', '', $text);

		$text = str_replace('</div>', '</span>', $text);
		$text = preg_replace('/<a href="javascript[^<]*?"><img id="image.*?" style="vertical-align: middle;border: none;" alt="" src=".*?" \/><\/a>/', '', $text);

		$text = preg_replace_callback(
			"/<span style=\"color:rgb\(([\s\S]*?)\);\">/msi",
			function ($matches) {
				foreach ($matches as $match)
					$match = rgb2html($match);
				{
					return "<span style=\"color:rgb$match;\">";
				}
			},
			$text
		);
		$text = preg_replace('/<!--.*?-->/', '', $text);
		$text = preg_replace('/<span class="masha_index.*?"><\/span>/', '', $text);
		$text = preg_replace("/<span style=\"font-size: 15px;\">[\s\S]*?<a class=\"highslide\" href=\"(.*?)\" target=\"_blank\"><img src=\".*?\" alt=\"\" class=\"fr-dib\"><\/a>/", "[img=right]$1[/img]\n\n", $text);

		$text = preg_replace('/<img src="\/uploads\/posts\/([^<]*?)\" [^<]*?>/', '[img]https://torrent-windows.net/uploads/posts/$1[/img]', $text);
		$text = str_replace('<br>', "\n", $text);
		$text = str_replace('<br />', "\n", $text);
		$text = str_replace('<hr>', '[hr]', $text);
		$text = str_replace('<u>', "[u]", $text);
		$text = str_replace('</u>', "[/u]", $text);
		$text = str_replace('<b>', "[b]", $text);
		$text = str_replace('</b>', "[/b]", $text);
		$text = str_replace('<i>', "[i]", $text);
		$text = str_replace('</i>', "[/i]", $text);
		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('</li>', '', $text);
		$text = preg_replace('/<span class="page__text full-text">.*?<\/span>/s', '', $text);
		$text = preg_replace('/<span class="page__text full-text">.*?<\/a>/s', '', $text);
		/*
		$text = preg_replace('/<!--dle_spoiler.*? -->/', '', $text);

		$text = preg_replace('/<!--spoiler[\s\S]*?-->/', '', $text);

		$text = preg_replace('/<div class="v_text">[\s\S]*?<a href=".*?={name}">[\s\S]*?<\/a><\/center>[\s\S]*?<\/center>/', '', $text);

		$text = preg_replace('/<div style="text-align:([\s\S]*?);">([\s\S]*?)<(?=\/)\/div>/', '[align=$1]$2[/align]', $text);

		$text = preg_replace('/<a href="(.*?)" rel="highslide" class="highslide"><img src=".*?" alt=\'.*?\' title=\'.*?\'  \/><\/a>/', '[img=right]$1[/img]', $text);
		$text = preg_replace('/<img src="\/uploads\/posts\/([\s\S]*?)\" alt=".*?" title=".*?">/', '[img]https://torrent-windows.net/uploads/posts/$1[/img]', $text);
	*/

		$text = preg_replace('/<a href="javascript[^<]*?">([^<]*?)<(?=\/)\/a>/', '$1', $text);

//dump($text);

		$text = preg_replace('/<center><a href="https.*?sub2.bubblesmedia.ru.*?"><img src="\/.*?.jpg"><\/a><\/center>/', '', $text);
		$text = preg_replace('/<center><a href=".*?" target="_blank"><span.*?><noindex>.*?<\/noindex><\/span><\/a>.*?<\/center>/', '', $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<span style="font-style:italic;">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<span style="font-size:([^<]*?)pt;">([^<]*?)<(?=\/)\/span>/', '[size=\\1]\\2[/size]', $text);
			$text = preg_replace('/<span style="font-size:([^<]*?)px;line-height:normal;">([^<]*?)<(?=\/)\/span>/', '[size=\\1]\\2[/size]', $text);
			$text = preg_replace('/<a href="([^<]*?)".*?target="_blank".*?>([^<]*?)<\/a>/', '[url=$1]$2[/url]  ', $text);
			$text = preg_replace('/<a href="([\s\S]*?)".*?target="_blank" rel="noopener external noreferrer">([\s\S]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<span style="color:rgb([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span style="color:([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span style="font-weight:bold;">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<span style="text-align:([^<]*?);display:block;">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
			$text = preg_replace('/<span align="([^<]*?)">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\\2[/align]", $text);

			$text = preg_replace('/<span style="text-decoration:underline;">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<span class="title_spoiler">([^<]*?)<(?=\/)\/span><span[^<]*?class="text_spoiler" style="display:none;">([^<]*?)<(?=\/)\/span>/', '[spoiler="$1"]$2[/spoiler]', $text);
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
			//$text = preg_replace('/<!--spoiler_title-->([\s\S]*?)<!--spoiler_title_end-->.*?<!--spoiler_text-->([\s\S]*?)<!--spoiler_text_end--><(?=\/)\/div>/', "[spoiler=\\1]\n\\2\n[/spoiler]", $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
		}


		if (strlen($text) > 55) {
		} else {
			$text = preg_replace('#<br />(.*?):#', "[b]$1:[/b] ", $text);
			$text = preg_replace('#<br>(.*?):#', "[b]$1:[/b] ", $text);
		}
		$text = preg_replace('/<a class="highslide" href="(.*?)" target="_blank"><img src=".*?" alt="" class="fr-dib"><\/a>/', '[th]$1[/th]', $text);
		$text = str_replace('Скачать торрент:', "", $text);
		$text = str_replace('Состав раздачи', "", $text);
		$text = str_replace('Показать / Скрыть текст', "", $text);
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		//$text = str_replace('Версия программы:', "[b]Версия программы:[/b]", $text);


		// Убираем пустое пространство
		//$text = preg_replace('#([\r\n])[\s]+#is', "$1", $text);
		$text = strip_tags(html_entity_decode($text));
	}
//dump($text);
	$text = preg_replace_callback('/<a href=".*?">(.*?)<\/a>/', function ($v) use ($server_name) {
		return "[url=https://$server_name/tracker.php?" . http_build_query(array('nm' => $v[1])) . ']' . $v[1] . '[/url]';
	},
		$text);
	return $text;
}

function riperam($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all('#<div class="h1cla"><h1>([\s\S]*?)</h1></div>#', $text, $source, PREG_SET_ORDER);
		$text = @$source[0][1];
		$text = str_replace(' - Скачать торрент бесплатно', '', $text);

	} elseif ($mode == 'torrent') {
		preg_match_all('#<a href=\"./download/file.php\?id=(.*?)\"><b>#', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		//var_dump($text);
	} else {
		preg_match_all('/<div class=\"content"\>([\s\S]*?)<\/td><td style="vertical-align: top; padding-top: 30px;">/', $text, $source, PREG_SET_ORDER);

		preg_match_all('/<a href="(.*?)" rel="prettyPhotoPosters.*?"><img src=".*?".*?><\/a>/', $text, $pic, PREG_SET_ORDER);
		$poster = ($pic[0][1]) ? "[img=right]" . $pic[0][1] . "[/img]\n\n" : "";
		$text = $poster . @$source[0][1];

		$text = preg_replace('/<div class="content">/', '', $text);
		$text = str_replace('<hr/>', "\n[hr]\n", $text);

		$text = str_replace('./go.html?', '', $text);
		$text = str_replace('?ref_=tt_mv_close', '', $text);
		$text = str_replace('<br/>', "\n", $text);
		$text = preg_replace('#</td><td style="vertical-align: top; padding-top: 30px;">[\s\S]*?<table[\s\S]*?><tr><td>#', "", $text);
		$text = str_replace('<div class="clear"></div>', "", $text);

		$text = preg_replace('/<var title="(.*?)" class="postImg".*?><\/var>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<img width=".*?" src="(.*?)" alt="">/', '[img]$1[/img]', $text);
		$text = preg_replace('/<img width=".*?" src="(.*?)" alt>/', '[img]$1[/img]', $text);
		//var_dump($text);
		$text = preg_replace('/<a href="(.*?)" rel="prettyPhotoSscreen.*?">.*?<\/a>/', '[th]$1[/th]', $text);
		//var_dump($text);

		$text = preg_replace('/<iframe.*?src="([^<]*?)" frameborder.*?allowfullscreen><\/iframe>/', '[youtube]$1[/youtube]', $text);
		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<span style="font-style: italic">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<span style="font-weight: bold">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<span style="text-decoration: underline">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<div style="text-align: ([\s\S]*?);">([\s\S]*?)<(?=\/)\/div>/', '[align=$1]$2[/align]', $text);
			$text = preg_replace('/<span style="color: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span style="font-size: ([^<]*?)0%; line-height:[^<]*?">([^<]*?)<(?=\/)\/span>/', '[size=\\1]\\2[/size]', $text);
			$text = preg_replace('/<a href="([^<]*?)" class="postlink[^<]*?" rel="nofollow" onclick="[^<]*?">([^<]*?)<\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<div class="sp-wrap"><div class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/div><(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
								//$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
			*/
		}
		$text = preg_replace('#\[url=http[^<]*?imdb.com/title/([^<]*?)\][^<]*?\[\/url\]#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)\].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)\].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);

		$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
		//dump($text);
		// Убираем пустое пространство
		//$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);
		$text = strip_tags(html_entity_decode($text));
	}
	return $text;
}

function mptor($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all("#<H1>([\s\S]*?)</H1>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a.*?href=\"/download/([\d]+)\">#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		preg_match_all("#<tr><td style=\"vertical-align:top;\">([\s\S]*?)</td></tr>#si", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		$text = preg_replace("/<\/td><td><img src ='([^<]*?)'\/>/", '[img=right]$1[/img]', $text);
		$text = preg_replace('/<\/td><td>.*?<img src="([\s\S]*?)" \/>/', '[img=right]$1[/img]', $text);
		//$text = preg_replace('/float: [\d]+;/', '', $text);
		$text = preg_replace("/<img src = '(.*?)' style='float: [\d]+;'\/>/", '[img]$1[/img]', $text);

		$text = preg_replace("/Релиз от.*?<br \/>/siu", "\n", $text);  // вырезает
		$text = preg_replace("/Автор рипа.*?<br \/>/siu", "\n", $text); // вырезает
		$text = preg_replace("/АНОНСЫ МОИХ РАЗДАЧ.*?<br \/>/siu", "\n", $text); // вырезает
		$text = preg_replace("/Рип от.*?<br \/>/siu", "\n", $text); // вырезает
		$text = preg_replace("/Раздача от.*?<br \/>/i", "\n", $text); // вырезает
		$text = preg_replace("/Сравнение с исходником.*?<br \/>/siu", "\n", $text); // вырезает
		$text = preg_replace("/screenshotcomparison.com.*?<br \/>/siu", "\n", $text); // вырезает
		$text = preg_replace("/Скачать.*?<br \/>/siu", "\n", $text); // вырезает
		$text = preg_replace("/источник.*?<br \/>/siu", "\n", $text); // вырезает

		//$text = preg_replace('/<td>.*?<img src="([\s\S]*?)" \/>/i', '[img=right]$1[/img]', $text);
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
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);

			$text = preg_replace('/<div class="sp-head folded clickable".*?>([^<]*?)<(?=\/)\/div><div class="sp-body" style="display: none;">([\s\S]*?)<(?=\/)\/div><(?=\/)\/div>/', '[spoiler="$1"]$2[/spoiler]', $text);
		}
		$text = preg_replace('#\[url=http.*?imdb.com/title/(.*?)].*?\[\/url\]#', '[imdb]https://www.imdb.com/title/$1[/imdb]', $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);

		//$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);
		$text = strip_tags(html_entity_decode($text));
	}
	//dump($text);
	return $text;
}

function tapochek($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all("#<h1 class=\"maintitle\"><a href=\".*?\">([\s\S]*?)</a></h1>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];

		$text = str_replace('<wbr>', '', $text);
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a href=\"download.php\?id=(.*?)\" class=\".*?\">#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];

	} else {
		$pos = strpos($text, '<div class="post_body"');
		$text = substr($text, $pos);
		$pos = strpos($text, '<div class="spacer_8"></div>');
		$text = substr($text, 0, $pos);
		$text = str_replace('<div class="post_body">', '', $text);
		$text = str_replace('<wbr>', '', $text);

		$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
		$text = str_replace('<div class="q-wrap">', '', $text);
		$text = str_replace('<div class="sp-wrap">', '', $text);
		$text = str_replace('<div class="c-wrap">', '', $text);
		$text = str_replace('<div class="clear"></div>', '', $text);
		//$text = preg_replace('/<a href="http.*?tapochek.net.*?" class="postLink"><var class="postImg" title=".*?">&#10;<\/var><\/a>/', "", $text);

		$text = preg_replace('/<img src="([^<]*?)" style="float: ([^<]*?);" class="glossy iradius20 horizontal" \/>/', "[img=$2]$1[/img]\n", $text);
		$text = preg_replace('/<var class="postImg postImgAligned img-([^<]*?)" title="([^<]*?)"><\/var>/', "[img=$1]$2[/img]\n", $text);
		$text = preg_replace('/<div align=".*?">.*?<\/div>/', "", $text);

		$text = preg_replace('/<div style="display: none;">.*?<\/div>/', '', $text);

		$text = str_replace('<hr />', "[hr]", $text);
		$text = preg_replace('/<var class="postImg" title="([^<]*?)"><\/var>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<var class="postImg postImgAligned img-([^<]*?)" title="([^<]*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);

		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('</li>', '', $text);
		$text = str_replace('<ol type="1">', '[list=1]', $text);
		$text = str_replace('<ol type="a">', '[list=a]', $text);
		$text = str_replace('</ol>', '[/list]', $text);
		$text = str_replace('<div></div>', "\n", $text);
		$text = preg_replace('/<a href="([^<]*?)" rel=".*?" class="zoom".*?><img src=".*?" \/> <\/a>/', '[th]$1[/th]', $text);
		$text = preg_replace('/<a href="([^<]*?)" rel=".*?"><img src=".*?" \/>&nbsp;<\/a>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<object.*?<param name="movie" value="([^<]*?)"><\/param>.*?<\/object>/', '[youtube]$1[/youtube]', $text);

		$text = str_replace('<br clear="all" />', "\n[br]\n", $text);
		$text = str_replace('<br />', "\n", $text);

		$text = str_replace('<div', '<span', $text);
		$text = str_replace('</div>', '</span>', $text);
		$text = str_replace('<a', '<span', $text);
		$text = str_replace('</a>', '</span>', $text);

		$text = str_replace('&#235;', "ë", $text);
		$text = str_replace('&#255;', "ÿ", $text);
		$text = str_replace('&#233;', "é", $text);
		$text = str_replace('&#246;', "ö", $text);
		$text = str_replace('&#252;', "ü", $text);
		$text = str_replace('&#228;', "ä", $text);
		$text = str_replace('&#244;', 'ô', $text);
		$text = str_replace('&#231;', 'ç', $text);
		$text = str_replace('&#201;', 'é', $text);
		$text = str_replace('&#225;', 'á', $text);
		$text = str_replace('&#189;', '½', $text);
		$text = str_replace('&#58;', ':', $text);
		$text = str_replace('&#039;', "'", $text);
		$text = str_replace('&#41;', ")", $text);
		$text = str_replace('&#40;', "(", $text);
		$text = str_replace('&#amp;', " ", $text);
		$text = str_replace('&#10;', "", $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&#238;', "î", $text);
		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('&#229;', 'å', $text);
		$text = str_replace('&quot;', '"', $text);

		for ($i = 0; $i <= 20; $i++) {
			//$text = preg_replace('/<span href="http.*?tapochek.net.*?" class="postLink">[^<]*?<(?=\/)\/span>/', "", $text);
			$text = preg_replace('/<span style="font-weight: bold;">([\s\S]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<span class="post-s">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
			$text = preg_replace('/<span style="text-decoration: underline;">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<span style="font-style: italic;">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('/<span style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?);">([\s\S]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span style="font-style: italic;color:([^<]*?);".*?>([^<]*?)<(?=\/)\/span>/', '[i][color=$1]$2[/color][/i]', $text);

			$text = preg_replace('/<span href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<span class="sp-body" title="([^<]*?)">([\s\S]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			//$text = preg_replace('/<span class="sp-body">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[spoiler]\n\\1\n[/spoiler]", $text);
			$text = preg_replace('/<span class="q">([^<]*?)<(?=\/)\/span>([^<]*?)<([^<]*?)\/span>/', "[quote]\n\\1\n[/quote]", $text);
			$text = preg_replace('/<span class="q" head="([^<]*?)">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
			$text = preg_replace('/<span class="c-head">.*?<span class="c-body">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[code]\n\\1\n[/code]", $text);
			$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
			$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
			$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
			$text = preg_replace('#\[url=http.*?imdb.com/title/(.*?)/].*?\[\/url\]#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
			$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
			$text = preg_replace('/\[url=.*?tapochek.net.*?\].*?\[\/url\]/', "", $text);

		}
//dump($text);

		$text = strip_tags(html_entity_decode($text));
	}
	return $text;
}

function uniongang($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all("#<a class=\"index\" href=\"download.php\?id=.*?\"><b>([\s\S]*?)</b></a>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a class=\"index\" href=\"download.php\?id=(\d+)\">#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		preg_match_all("#\" /><br />([\s\S]*?)<tr><td width=\"\" class=\"heading\" valign=\"top\" align=\"right\">Тип</td>#", $text, $source, PREG_SET_ORDER);
		preg_match_all('/<img class="linked-image" src="([^<]*?)" border="0"/', $text, $pic, PREG_SET_ORDER);
		$poster = ($pic[0][1]) ? "[img=right]" . $pic[0][1] . "[/img]" : "";
		$text = $poster . $source[0][1];
		$text = preg_replace('/<script type="text\/javascript">[\s\S]*?<\/script>/', '', $text);
		$text = preg_replace('/<script.*?script>/', '', $text);
		$text = preg_replace('/<td.*?>.*?<\/td>/', '', $text);
		$text = preg_replace('/<embed.*?embed>/', '', $text);
		//$text = preg_replace('/<noindex.*?noindex>/', '', $text);
		$text = str_replace('<div class="clearer"></div>', '', $text);
		$text = str_replace('<br />', "", $text);

		$text = str_replace('<center>', "[align=center]", $text);
		$text = str_replace('</center>', "[/align]", $text);

		$text = str_replace('<textarea>', '', $text);
		$text = str_replace('</textarea>', '', $text);
		$text = str_replace('<noindex>', '', $text);
		$text = str_replace('</noindex>', '', $text);
		$text = preg_replace('/<div class="galPicList daGallery">([\s\S]*?)<\/div>/', "[spoiler=\"Скриншоты\"]\n[align=center]\\1[/align][/spoiler]", $text);


		$text = preg_replace('/<a href="http[^<]*?kinopoisk.ru\/film\/(\d+)\/".*?><img.*?><(?=\/)\/a>/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('/<a href="http[^<]*?kinopoisk.ru\/film.*?-[0-9]{4}-\/(\d+)\/".*?><img.*?><(?=\/)\/a>/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('/<a href="http[^<]*?kinopoisk.ru\/level\/.*?\/film\/(\d+)\/".*?><img.*?><(?=\/)\/a>/', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
		$text = preg_replace('/<a href="http[^<]*?imdb.com\/title\/(\w+\d+)\/".*?><img.*?><(?=\/)\/a>/', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);

		$text = preg_replace('/<object .*? value=\'([^<]*?)\'.*?<\/object>/', '[youtube]$1[/youtube]', $text);
		$text = str_replace('youtube.com/v/', 'youtube.com/watch?v=', $text);
		$text = preg_replace('/&hl=ru&fs=1&/', '', $text);
		$text = preg_replace('/<img class="linked-image" src="([^<]*?)".*?\/>/', "[img]\\1[/img]", $text);
		$text = preg_replace('/<img border="0" src=".*?">/', '', $text);
		$text = preg_replace('/<a href="[^<]*?uniongang.[^<]*?" title=".*?">[^<]*?<\/a>/', '', $text);

		$text = str_replace('&#039;', "'", $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<b>([^<]*?)<(?=\/)\/b>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<u>([^<]*?)<(?=\/)\/u>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<i>([^<]*?)<(?=\/)\/i>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<s>([^<]*?)<(?=\/)\/s>/', '[s]$1[/s]', $text);
			$text = preg_replace('/<a href="([^<]*?)" class="gPic" rel="galI".*?><img.*?><\/a>/', "[th]$1[/th]", $text);
			$text = preg_replace('/<font style="font-size: ([^<]*?)pt">([^<]*?)<(?=\/)\/font>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\\2[/align]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<a href="([^<]*?)" tooltip=".*?">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<a href="([^<]*?)" title=".*?">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<a href="\/([^<]*?)">([^<]*?)<(?=\/)\/a>/', '[url=http://www.uniongang.tv/$1]$2[/url]', $text);
			$text = preg_replace('/<a href="\/([^<]*?)">([^<]*?)<(?=\/)\/a>/', '[url=http://www.uniongang.club/$1]$2[/url]', $text);
			$text = preg_replace('/<div class="spoiler-wrap"><div class="spoiler-head folded clickable">([^<]*?)<\/div><div class="spoiler-body">([^<]*?)<(?=\/)\/div><(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);

		}
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		$text = str_replace('</td></tr><tr>', '', $text);
		$text = preg_replace('/<td valign=middle align=right>(.*?)<\/td><td>/', "\n\n\\1: ", $text);
		$text = strip_tags(html_entity_decode($text));
	}
	return $text;
}

function kinozal($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all("#<a href=\".*?\" class=\"r\d+\">([\s\S]*?)</a>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a href=\"[^<]*?download.php\?id=(\d+)\" title=\".*?\"><img src=\"/pic/dwn_torrent.gif\".*?</a>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		$match_screen = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Скриншоты<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_id = $source[0][1];
		$pagesd = $source[0][2];
		if ($match_screen) {
			$screen = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_id&pagesd=$pagesd");
			$screenschot = "\n[spoiler=\"Скриншоты\"][align=center]" . $screen . "[/align][/spoiler]";
		} else
			$screenschot = false;

		$match_screen2 = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Качество<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_id2 = $source[0][1];
		$pagesd2 = $source[0][2];
		if ($match_screen2) {
			$screen2 = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_id2&pagesd=$pagesd2");
			$screenschot2 = "\n[spoiler=\"Качество\"][align=center]" . $screen2 . "[/align][/spoiler]";
		} else
			$screenschot2 = false;

		$match_tr = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Треклист<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_idtr = $source[0][1];
		$pagesdtr = $source[0][2];
		if ($match_tr) {
			$tr = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_idtr&pagesd=$pagesdtr");
			$track = "\n[spoiler=\"Треклист\"]\n[list]\n" . $tr . "\n[/list]\n[/spoiler]";
		} else
			$track = false;

		$match_movie_content = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Содержание<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_idm = $source[0][1];
		$pagesdm = $source[0][2];
		if ($match_movie_content) {
			$movie_content = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_idm&pagesd=$pagesdm");
			$movie = "\n[spoiler=\"Содержание\"]\n" . $movie_content . "\n[/spoiler]";
		} else
			$movie = false;

		$match_cover = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Обложки<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_idcv = $source[0][1];
		$pagesdcv = $source[0][2];
		if ($match_cover) {
			$content_cover = file_get_contents("http://kinozal.tv/get_srv_details.php?id=$url_idcv&pagesd=$pagesdcv");
			$cover = "\n[spoiler=\"Обложки\"]\n[align=center]" . $content_cover . "[/center]\n[/spoiler]";
		} else
			$cover = false;

		$text = preg_replace('#kinopoisk.ru\/film\/(\d+)\/#', "/film/$1", $text);
		$text = preg_replace('#/i/poster/#', "http://kinozal.tv/i/poster/$1", $text);

		preg_match_all('/<li class="img"><a href="\/details.*?".*?><img src="([^<]*?)" .*?><\/a><\/li>/', $text, $pic, PREG_SET_ORDER);
		$poster = ($pic[0][1]) ? "[case]" . $pic[0][1] . "[/case]\n" : "";

		preg_match_all("#class=\"cat_img_r\"([\s\S]*?)<div class=\"bx2_0\">#si", $text, $source, PREG_SET_ORDER);

		preg_match_all('/<a href="http[^<]*?imdb.com\/title\/(\w+\d+)\/" target="_blank">IMDb.*?<\/a>/', $text, $imdb, PREG_SET_ORDER);
		$imdb_rating = ($imdb[0][1]) ? "[imdb]https://www.imdb.com/title/" . $imdb[0][1] . "[/imdb]" : "";

		preg_match_all('/<a href="http[^<]*?kinopoisk.ru\/film\/(\d+)" target="_blank">Кинопоиск.*?<\/a>/', $text, $kp, PREG_SET_ORDER);

		$kp_rating = ($kp[0][1]) ? "[kp]https://www.kinopoisk.ru/film/" . $kp[0][1] . "[/kp]" : "";
		$text = $poster . $source[0][1] . $imdb_rating . "&nbsp;" . $kp_rating . $track . $movie . $screenschot . $cover . $screenschot2;

		$text = preg_replace('/<div class="bx1 justify">([\s\S]*?)<\/div>/', "\n[hr]\\1", $text);

		$text = preg_replace('/<script.*?script>/', '', $text);

		$text = preg_replace('/<td.*?>.*?<\/td>/', '', $text);
		$text = preg_replace('/<li.*?>.*?<\/li>/', "\n[hr]", $text);
		$text = preg_replace("/\n\[hr\]\n\[hr\]\n\[hr\]\n\[hr\]/", "\n[hr]\n[hr]\n", $text);
		$text = preg_replace('/<a href=".*?" class="sba" target="_blank">([\s\S]*?)<\/a>/', '[color=blue]\\1[/color]', $text);
		$text = str_replace('<div class="clearer"></div>', '', $text);
		$text = str_replace('<br />', "", $text);
		$text = str_replace('Подобные раздачи не найдены', "", $text);

		$text = str_replace('<center>', "[align=center]", $text);
		$text = str_replace('</center>', "[/align]", $text);

		$text = preg_replace('/<img src="([^<]*?)" alt="">/', "[img]\\1[/img]", $text);
		$text = preg_replace('/<img border="0" src=".*?">/', '', $text);

		$text = str_replace('&#039;', "'", $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<b>([^<]*?)<(?=\/)\/b>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<u>([^<]*?)<(?=\/)\/u>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<i>([^<]*?)<(?=\/)\/i>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<s>([^<]*?)<(?=\/)\/s>/', '[s]$1[/s]', $text);
			$text = preg_replace('/<a href="([^<]*?)" class="gPic" rel="galI".*?><img.*?><\/a>/', "[th]$1[/th]", $text);
			$text = preg_replace('/<font style="font-size: ([^<]*?)pt">([^<]*?)<(?=\/)\/font>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\\2[/align]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);

			$text = preg_replace('/<a href="([^<]*?)" target="_blank">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<div class="spoiler-wrap"><div class="spoiler-head folded clickable">([^<]*?)<\/div><div class="spoiler-body">([^<]*?)<(?=\/)\/div><(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);

		}
		$text = preg_replace('#\[url=[^<]*?kinozal.*?\](.*?)\[/url\]#', "\\1", $text);
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);

		// Вставка плеера
		insert_video_player($text);

		$text = strip_tags(html_entity_decode($text));
		$text = preg_replace('/onclick="[\s\S]*?" alt="">/', '', $text);
		$text = preg_replace("/[hr]\n[hr]\n[hr]\n/", "[hr]\n", $text);
	}
	return $text;
}

function kinozalguru($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all("#<a href=\".*?\" class=\"r\d+\">([\s\S]*?)</a>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a href=\"[^<]*?download.php\?id=(\d+)\" title=\".*?\"><img src=\"/pic/dwn_torrent.gif\".*?</a>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		$match_screen = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Скриншоты<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_id = $source[0][1];
		$pagesd = $source[0][2];
		if ($match_screen) {
			$screen = file_get_contents("http://kinozal.guru/get_srv_details.php?id=$url_id&pagesd=$pagesd");
			$screenschot = "\n[spoiler=\"Скриншоты\"][align=center]" . $screen . "[/align][/spoiler]";
		} else
			$screenschot = false;

		$match_screen2 = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Качество<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_id2 = $source[0][1];
		$pagesd2 = $source[0][2];
		if ($match_screen2) {
			$screen2 = file_get_contents("http://kinozal.guru/get_srv_details.php?id=$url_id2&pagesd=$pagesd2");
			$screenschot2 = "\n[spoiler=\"Качество\"][align=center]" . $screen2 . "[/align][/spoiler]";
		} else
			$screenschot2 = false;

		$match_tr = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Треклист<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_idtr = $source[0][1];
		$pagesdtr = $source[0][2];
		if ($match_tr) {
			$tr = file_get_contents("http://kinozal.guru/get_srv_details.php?id=$url_idtr&pagesd=$pagesdtr");
			$track = "\n[spoiler=\"Треклист\"]\n[list]\n" . $tr . "\n[/list]\n[/spoiler]";
		} else
			$track = false;

		$match_movie_content = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Содержание<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_idm = $source[0][1];
		$pagesdm = $source[0][2];
		if ($match_movie_content) {
			$movie_content = file_get_contents("http://kinozal.guru/get_srv_details.php?id=$url_idm&pagesd=$pagesdm");
			$movie = "\n[spoiler=\"Содержание\"]\n" . $movie_content . "\n[/spoiler]";
		} else
			$movie = false;

		$match_cover = preg_match_all("/<a onclick=\"showtab\((\d+),(\d+)\); return false;\" href=\"#\">Обложки<\/a>/", $text, $source, PREG_SET_ORDER);
		$url_idcv = $source[0][1];
		$pagesdcv = $source[0][2];
		if ($match_cover) {
			$content_cover = file_get_contents("http://kinozal.guru/get_srv_details.php?id=$url_idcv&pagesd=$pagesdcv");
			$cover = "\n[spoiler=\"Обложки\"]\n[align=center]" . $content_cover . "[/center]\n[/spoiler]";
		} else
			$cover = false;

		$text = preg_replace('#kinopoisk.ru\/film\/(\d+)\/#', "/film/$1", $text);
		$text = preg_replace('#/i/poster/#', "http://kinozal.tv/i/poster/$1", $text);

		preg_match_all('/<li class="img"><a href="\/details.*?".*?><img src="([^<]*?)" .*?><\/a><\/li>/', $text, $pic, PREG_SET_ORDER);
		$poster = ($pic[0][1]) ? "[case]" . $pic[0][1] . "[/case]\n" : "";

		preg_match_all("#class=\"cat_img_r\"([\s\S]*?)<div class=\"bx2_0\">#si", $text, $source, PREG_SET_ORDER);

		preg_match_all('/<a href="http[^<]*?imdb.com\/title\/(\w+\d+)\/" target="_blank">IMDb.*?<\/a>/', $text, $imdb, PREG_SET_ORDER);
		$imdb_rating = ($imdb[0][1]) ? "[imdb]https://www.imdb.com/title/" . $imdb[0][1] . "[/imdb]" : "";

		preg_match_all('/<a href="http[^<]*?kinopoisk.ru\/film\/(\d+)" target="_blank">Кинопоиск.*?<\/a>/', $text, $kp, PREG_SET_ORDER);

		$kp_rating = ($kp[0][1]) ? "[kp]https://www.kinopoisk.ru/film/" . $kp[0][1] . "[/kp]" : "";
		$text = $poster . $source[0][1] . $imdb_rating . "&nbsp;" . $kp_rating . $track . $movie . $screenschot . $cover . $screenschot2;

		$text = preg_replace('/<div class="bx1 justify">([\s\S]*?)<\/div>/', "\n[hr]\\1", $text);

		$text = preg_replace('/<script.*?script>/', '', $text);

		$text = preg_replace('/<td.*?>.*?<\/td>/', '', $text);
		$text = preg_replace('/<li.*?>.*?<\/li>/', "\n[hr]", $text);
		$text = preg_replace("/\n\[hr\]\n\[hr\]\n\[hr\]\n\[hr\]/", "\n[hr]\n[hr]\n", $text);
		$text = preg_replace('/<a href=".*?" class="sba" target="_blank">([\s\S]*?)<\/a>/', '[color=blue]\\1[/color]', $text);
		$text = str_replace('<div class="clearer"></div>', '', $text);
		$text = str_replace('<br />', "", $text);
		$text = str_replace('Подобные раздачи не найдены', "", $text);

		$text = str_replace('<center>', "[align=center]", $text);
		$text = str_replace('</center>', "[/align]", $text);

		$text = preg_replace('/<img src="([^<]*?)" alt="">/', "[img]\\1[/img]", $text);
		$text = preg_replace('/<img border="0" src=".*?">/', '', $text);

		$text = str_replace('&#039;', "'", $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<b>([^<]*?)<(?=\/)\/b>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<u>([^<]*?)<(?=\/)\/u>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<i>([^<]*?)<(?=\/)\/i>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<s>([^<]*?)<(?=\/)\/s>/', '[s]$1[/s]', $text);
			$text = preg_replace('/<a href="([^<]*?)" class="gPic" rel="galI".*?><img.*?><\/a>/', "[th]$1[/th]", $text);
			$text = preg_replace('/<font style="font-size: ([^<]*?)pt">([^<]*?)<(?=\/)\/font>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\\2[/align]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);

			$text = preg_replace('/<a href="([^<]*?)" target="_blank">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<div class="spoiler-wrap"><div class="spoiler-head folded clickable">([^<]*?)<\/div><div class="spoiler-body">([^<]*?)<(?=\/)\/div><(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
		}
		$text = preg_replace('#\[url=[^<]*?kinozal.*?\](.*?)\[/url\]#', "\\1", $text);
		$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);

		// Вставка плеера
		insert_video_player($text);

		$text = strip_tags(html_entity_decode($text));
		$text = preg_replace('/onclick="[\s\S]*?" alt="">/', '', $text);
		$text = preg_replace("/[hr]\n[hr]\n[hr]\n/", "[hr]\n", $text);
	}
	return $text;
}

function windowssoftinfo($text, $mode = '')
{
	global $bb_cfg;

	$server_name = $bb_cfg['server_name'];
	$sitename = $bb_cfg['sitename'];

	if ($mode == 'title') {
		preg_match_all("#<h1 class=\"fstory-h1\">([\s\S]*?)</h1>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a href=\".*?engine/download.php\?id=(\d+)\" class=\"btn_red\">#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {

		preg_match_all('/<img src="([^<]*?)" style="height:\d+px;width:\d+px" alt="poster"\/>/', $text, $pic, PREG_SET_ORDER);
		$poster = ($pic[0][1]) ? "[img=right]" . $pic[0][1] . "[/img]\n" : "";

		preg_match_all("#<div class=\"fstory-content margin-b20\">([\s\S]*?)<center><center>#si", $text, $source, PREG_SET_ORDER);
		$text = $poster . $source[0][1];

		$text = preg_replace('/<!--sizestart:(\d+)--><span style="font-size.*?">/', '<size style="font-size:\\1">', $text);
		$text = preg_replace('/<\/span><!--\/sizeend-->/', "</size>", $text);
		$text = str_replace('&quot;', "", $text);
		$text = str_replace('&nbsp;', "", $text);
		$text = preg_replace('/<!--.*?-->/', '', $text);
		$text = preg_replace('/<!--\/.*?-->/', '', $text);
		$text = preg_replace('/<img id="image.*?>/', '', $text);
		$text = preg_replace('/<a href="javascript[\s\S]*?">/', '', $text);
		$text = preg_replace('/<div id=".*?" class="text_spoiler" style="display:none;">/', '<div class="text_spoiler">', $text);

		$text = preg_replace_callback(
			"/<div class=\"title_spoiler\"><\/a>([\s\S]*?)<\/a><\/div>/msi",
			function ($matches) {
				foreach ($matches as $match)
					$match = strip_tags($match);
				{
					return "<div class=\"title_spoiler\">$match</a></div>";
				}
			},
			$text
		);
		$text = preg_replace('/<img src="([^<]*?)" style=".*?;" data-maxwidth=".*?" alt=".*?">/', "[img]$1[/img]", $text);
		$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);
		$text = str_replace('<br>', "\n", $text);
		$text = preg_replace('/<div style="text-align:.*?;"><strong>Скачать.*?<\/strong><\/div>/', '', $text);
		$text = str_replace('youtube.com/embed/', "youtube.com/watch?v=", $text);
		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('</li>', '', $text);
		$text = preg_replace_callback('/<a href=".*?" >(.*?)<\/a>/', function ($v) use ($server_name) {
			return "[url=https://$server_name/tracker.php?" . http_build_query(array('nm' => $v[1])) . ']' . $v[1] . '[/url]';
		},
			$text);

		for ($i = 0; $i <= 20; $i++) {
			$text = str_replace('<u>', "[u]", $text);
			$text = str_replace('</u>', "[/u]", $text);
			$text = str_replace('<b>', "[b]", $text);
			$text = str_replace('</b>', "[/b]", $text);
			$text = str_replace('<i>', "[i]", $text);
			$text = str_replace('</i>', "[/i]", $text);
			$text = str_replace('<s>', "[s]", $text);
			$text = str_replace('</s>', "[/s]", $text);
			//$text = preg_replace('/<a href=".*?" >([^<]*?)<\/a>/', "[color=blue]\\1[/color]", $text);

			$text = preg_replace('/<size style="font-size:([^<]*?)">([^<]*?)<(?=\/)\/size>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="color:([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<div style="text-align:([^<]*?);">([^<]*?)<(?=\/)\/div>/', '[align=$1]$2[/align]', $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
			$text = preg_replace('/<a href="([^<]*?)"  target="_blank".*?>([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<div class="title_spoiler">([^<]*?)<\/a><(?=\/)\/div><div class="text_spoiler">([^<]*?)<(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]\n", $text);
		}

		$text = preg_replace('#\[url=http.*?imdb.com/title/(.*?)/].*?\[\/url\]#', '[imdb]https://www.imdb.com/title/$17[/imdb]', $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
		$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)/].*?\[\/url\]#', '[kp]https://www.kinopoisk.ru/film/$1[/kp]', $text);
		$text = preg_replace('/\[url=.*?torrents-club.info.*?\].*?\[\/url\]/', "", $text);
		$text = strip_tags(html_entity_decode($text));
		//dump($text);
	}
	return $text;
}

function ztorrents($text, $mode = false)
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all("#<title>([\s\S]*?)</title>#si", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		$text = str_replace('скачать через торрент', '', $text);

	} elseif ($mode == 'torrent') {
		preg_match_all('#Скачать торрент: <a href=\"([\s\S]*?)\">#', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		//var\dump($source);

	} else {

		$pos = strpos($text, '<div class="fullstory">');
		$text = substr($text, $pos);
		$pos = strpos($text, '<div style="padding: 10px; border-bottom: 1px solid #dbe8ed;">');
		$text = substr($text, 0, $pos);

		$text = str_replace('<div class="fullstory">', '', $text);
		$text = str_replace('<br>', "\n", $text);

		$text = preg_replace('/<!--.*?-->/', '', $text);
		$text = preg_replace('/<img id=.*?>/', '', $text);

		$text = preg_replace_callback(
			"/<span style=\"color:rgb\(([\s\S]*?)\);\">/msi",
			function ($matches) {
				foreach ($matches as $match)
					$match = rgb2html($match);
				{
					return "<span style=\"color:rgb$match;\">";
				}
			},
			$text
		);


		$text = str_replace('<div', '<span', $text);
		$text = str_replace('</div>', '</span>', $text);
		$text = str_replace('<a', '<span', $text);
		$text = str_replace('</a>', '</span>', $text);
		$text = str_replace('<sub>', "", $text);
		$text = str_replace('</sub>', "", $text);
		$text = str_replace('<sup>', "", $text);
		$text = str_replace('</sup>', "", $text);
		$text = str_replace('<tr>', '', $text);
		$text = str_replace('</tr>', '', $text);
		$text = preg_replace('/<td.*?>/', '', $text);
		$text = str_replace('</td>', '', $text);
		$text = str_replace('<ol>', '[list]', $text);
		$text = str_replace('</ol>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('</li>', '', $text);
		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('youtube.com/embed/', "youtube.com/watch?v=", $text);
		$text = preg_replace('/<iframe width=".*?" height=".*?" src=\"(.*?)\" frameborder="0".*?><\/iframe>/', '[align=center][youtube]$1[/youtube][/align]', $text);

		$text = preg_replace('/<img src="([^<]*?)" style="max-width:100%;" alt=".*?">/', '[img]https://z-torrents.ru$1[/img]', $text);
		$text = preg_replace('/<img src="([^<]*?)" style="float:([^<]*?);max-width:100%;" alt=".*?" title=".*?">/', "\n[img=\\2]\\1[/img]\n", $text);
		$text = preg_replace('/<span style="margin-.*?;">([^<]*?)<(?=\/)\/span>/', '$1', $text);

		for ($i = 0; $i <= 20; $i++) {

			$text = str_replace('<u>', "[u]", $text);
			$text = str_replace('</u>', "[/u]", $text);
			$text = str_replace('<b>', "[b]", $text);
			$text = str_replace('</b>', "[/b]", $text);
			$text = str_replace('<i>', "[i]", $text);
			$text = str_replace('</i>', "[/i]", $text);
			$text = str_replace('<s>', "[s]", $text);
			$text = str_replace('</s>', "[/s]", $text);
			$text = str_replace('<hr>', '[hr]', $text);
			$text = preg_replace('/<span style="color:rgb([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span style="text-align:([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[align=$1]$2[/align]', $text);
			$text = preg_replace('/<span style="font-family:([^<]*?),.*?;">([^<]*?)<(?=\/)\/span>/', '[font="$1"]$2[/font]', $text);
			$text = preg_replace('/<span style="font-size:([^<]*?)px;">([^<]*?)<(?=\/)\/span>/', '[size=\\1]\\2[/size]', $text);
			$text = preg_replace('/<span href="([^<]*?)" rel="external noopener noreferrer">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<span href="([^<]*?)" rel="noopener noreferrer external" target="_blank">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);

			$text = preg_replace('/<span class="title_spoiler">.*?<span href=".*?">([^<]*?)<(?=\/)\/span><(?=\/)\/span><span id=".*?" class="text_spoiler" style="display:none;">([^<]*?)<(?=\/)\/span>([^<]*?)<(?=\/)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]\n", $text);

		}
//dump($text);
		/*
							$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
							$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
							$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
							$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
							$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
							$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
		*/
		$text = preg_replace('/<span href=".*?">([\s\S]*?)<\/span>/', '$1', $text);

	}
	return $text;
}

function piratbit($text, $mode = false)
{
	global $bb_cfg;

	$server_name = $bb_cfg['server_name'];
	$sitename = $bb_cfg['sitename'];

	if ($mode == 'title') {
		preg_match_all("#<a class=\"tt-text\" href=\"[\s\S]*?\" title=\"[\s\S]*?\"><strong>([\s\S]*?) <span style=\"display: none\">[\s\S]*?</span></strong></a>#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all('#<td width="15%" rowspan="8" class="tCenter pad_6">[\s\S]*?href="/dl.php\?id=(\d+)"#i', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		//dump($text);
	} else {

		$pos = strpos($text, '<span id="ps_');
		$text = substr($text, $pos);
		$pos = strpos($text, '<div id="ph_');
		$text = substr($text, 0, $pos);
		$text = preg_replace('/<span id="ps_.*?">/', '', $text);
		$text = preg_replace('/<div id="ph_.*?">/', '', $text);
		$text = str_replace('?ref_=fn_al_tt_1', '', $text);
		$text = str_replace('?version=3&rel=0&iv_load_policy=3&egm=1&fs=1&showinfo=0', '', $text);
		$text = preg_replace('/http[^<]*?goo.php\?url=/', '', $text);
		$text = preg_replace('/<img class="smile" src=".*?" alt="" align="absmiddle" border="0" \/>/', '', $text);
		$text = preg_replace('/<span style="display: none">[^<]*?<(?=\/)\/span>/', '', $text);

		$text = preg_replace_callback('/<a href="\/tracker\/\?ss=([^<]*?)#results"\/>[^<]*?<(?=\/)\/a>/', function ($v) use ($server_name) {
			return "[url=https://$server_name/tracker.php?" . http_build_query(array('nm' => $v[1])) . ']' . $v[1] . '[/url]';
		},
			$text);
		$text = preg_replace('/<a href="([^<]*?)"[^<]*?><var[^<]*?title="http[^<]*?rating.kinopoisk.ru[^<]*?">&#10;<\/var><\/a>/', '[kp]$1[/kp]', $text);
		$text = preg_replace('/<a href="[^<]*?" target="_blank"><img src="\/ratings\/kinopoisk.php\?url=([^<]*?)" alt="[^<]*?" ><(?=\/)\/a>/', '[kp]$1[/kp]', $text);
		$text = preg_replace('/<a href="[^<]*?" target="_blank"><img src="\/ratings\/imdb.php\?url=([^<]*?)" alt="[^<]*?" ><(?=\/)\/a>/', '[imdb]$1[/imdb]', $text);
		$text = preg_replace('/<object width=".*?" height=".*?"><param name="movie" value="([^<]*?)">.*?<\/object>/', '[youtube]$1[/youtube]', $text);

		$text = preg_replace('/<var class="postImg postImgAligned pull-([^<]*?)" title="([^<]*?)">&#10;<(?=\/)\/var>/', "\n[img=$1]$2[/img]\n", $text);
		$text = preg_replace('/<var class="postImg" title="([^<]*?)">&#10;<(?=\/)\/var>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<a href="([^<]*?)" rel="gallery" class="fancybox"><img[^<]*?><(?=\/)\/a>/', '[th]$1[/th]', $text);

		$text = str_replace('<div', '<span', $text);
		$text = str_replace('</div>', '</span>', $text);
		$text = str_replace('<hr>', "\n[hr]\n", $text);
		$text = str_replace('<br>', "\n", $text);
		$text = str_replace('<b>', '[b]', $text);
		$text = str_replace('</b>', '[/b]', $text);
		$text = str_replace('<u>', '[u]', $text);
		$text = str_replace('</u>', '[/u]', $text);
		$text = str_replace('<i>', '[i]', $text);
		$text = str_replace('</i>', '[/i]', $text);
		$text = str_replace('<s>', '[s]', $text);
		$text = str_replace('</s>', '[/s]', $text);
		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = preg_replace('/<a name="[^<]*?"><(?=\/)\/a>/', '', $text);
		$text = str_replace('<pre class="post-pre">', '[pre]', $text);
		$text = str_replace('</pre>', '[/pre]', $text);
		/*
						$text = preg_replace('/http:([^<]*?)fastpic.ru/', 'https:$1fastpic.ru/', $text);
						$text = preg_replace('/http:([^<]*?)imageban.ru/', 'https:$1imageban.ru/', $text);
						$text = preg_replace('/http:([^<]*?)youpic.su/', 'https:$1youpic.su/', $text);
						$text = preg_replace('/http:([^<]*?)lostpic.net/', 'https:$1lostpic.net/', $text);
						$text = preg_replace('/http:([^<]*?)radikal.ru/', 'https:$1radikal.ru/', $text);
						$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
		*/
		$text = preg_replace('/http:([^<]*?)kinopoisk.ru/', 'https:$1kinopoisk.ru', $text);
		//$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', '', $text);


		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<span class="post_font_size_([^<]*?)" style="line-height: normal;">([^<]*?)<(?=\/)\/span>/', '[size=\\1]\\2[/size]', $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[font="\\1"]\\2[/font]', $text);
			$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span class="btn-block text-([^<]*?)">([^<]*?)<(?=\/)\/span>/', '[align=\\1]\\2[/align]', $text);
			$text = preg_replace('/<a href="([^<]*?)" target="_blank" rel="gallery" class="postLink fancybox">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<a href="([^<]*?)" rel="gallery" target="_blank" class="postLink fancybox fancybox-media">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<span class="sp-wrap"><span class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/span><(?=\/)\/span>/', "\n[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			$text = preg_replace('/<span class="sp-wrap"><span class="sp-body">([^<]*?)<(?=\/)\/span><(?=\/)\/span>/', "\n[spoiler]\n\\1\n[/spoiler]", $text);
			$text = preg_replace('/<span class="q-wrap"[^<]*?><p class="q-head"><span style="font-weight: bold;">[^<]*?<(?=\/)\/span><(?=\/)\/p><span class="c-body">([^<]*?)<(?=\/)\/span><(?=\/)\/span>/', "[code]\n\\1\n[/code]", $text);
		}
		//var_dump($text);
		$text = strip_tags(html_entity_decode($text));
	}

	return $text;
}

function onlysoft($text, $mode = '')
{
	global $bb_cfg;

	$server_name = $bb_cfg['server_name'];
	$sitename = $bb_cfg['sitename'];

	if ($mode == 'title') {
		preg_match_all('#<h1 class="maintitle">.*?<a class="tt-text" href=".*?">([\s\S]*?)</a>.*?</h1>#s', $text, $source, PREG_SET_ORDER);
		$text = @$source[0][1];
		$text = str_replace('<wbr>', '', $text);
	} elseif ($mode == 'torrent') {
		preg_match_all("#<a href=\"download.php\?id=(.*?)\" class#", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		$pos = strpos($text, '<span id="pp_');
		$text = substr($text, $pos);
		$pos = strpos($text, '<div id="pc_');
		$text = substr($text, 0, $pos);

		$text = preg_replace('#https://only-soft.org/tracker.php\?(.*?)nm=(.*?)#', "https://$server_name/tracker.php?nm=$2", $text);
		$text = str_replace('https://only-soft.org', "https://$server_name", $text);
		$text = preg_replace('#\n<h3 class="sp-title">.*?</h3>#', '', $text);
		$text = preg_replace('/<span id="pp_.*?">/', '', $text);
		$text = str_replace('<span class="post-br"><br /></span>', "\n\n", $text);

		$text = preg_replace('/<img class="smile" src=".*?" alt=".*?" align="absmiddle" border="0" \/>/', '', $text);
		$text = preg_replace('/\n<h3 class="sp-title">.*?<\/h3>/', '', $text);
		$text = str_replace('<div class="q-wrap">', '', $text);
		$text = str_replace('<div class="sp-wrap">', '', $text);
		$text = str_replace('<div class="c-wrap">', '', $text);
		$text = str_replace('<span class="post-hr">-</span>', "\n[hr]\n", $text);

		$text = preg_replace('/<var class="postImg" title="(.*?)">&#10;<\/var>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<var class="postImg postImgAligned img-(.*?)" title="(.*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
		$text = preg_replace('/<a href="([^<]*?)".*rel="topic" class="highslide"><img src=".*?".*?><\/a>/', '[th]$1[/th]', $text);
		$text = preg_replace('/\n/', "", $text);

		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('</li>', '', $text);

		$text = str_replace('&#039;', "'", $text);
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&gt;', '>', $text);
		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('<br />', "\n", $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<span class="post-b">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<span class="post-u">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<span class="post-i">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<span class="post-s">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
			$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
			$text = preg_replace('/<div style="margin-left: 2em">([^<]*?)<([^<]*?)\/div>/', "[list]$1[/list]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<a rel="nofollow" href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<a href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/a>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<div class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/div>[^<]*?<(?=\/)\/div>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			$text = preg_replace('/<div class="q">([^<]*?)<(?=\/)\/div>([^<]*?)<([^<]*?)\/div>/', "[quote]\n\\1\n[/quote]", $text);
			$text = preg_replace('/<div class="q" head="([^<]*?)">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
			$text = preg_replace('/<div class="c-body">([^<]*?)<(?=\/)\/div>([\s\S]*?)<([^<]*?)\/div>/', "[code]\n\\1\n[/code]", $text);
			/*
								$text = preg_replace('/http:(.*?)fastpic.ru/', "https:$1fastpic.ru/", $text);
								$text = preg_replace('/http:(.*?)imageban.ru/', "https:$1imageban.ru/", $text);
								$text = preg_replace('/http:(.*?)youpic.su/', "https:$1youpic.su/", $text);
								$text = preg_replace('/http:(.*?)lostpic.net/', "https:$1lostpic.net/", $text);
								$text = preg_replace('/http:(.*?)radikal.ru/', "https:$1radikal.ru/", $text);
								$text = str_replace('http://img-fotki.yandex.ru', 'https://img-fotki.yandex.ru', $text);
			*/
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
			$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		}
		// Убираем пустое пространство
		//$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);
		$text = strip_tags(html_entity_decode($text));
		//ump($text);
	}
	return $text;
}

function rutrackerru($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all('#<title>([\s\S]*?) :: RuTracker.RU - Свободный торрент трекер</title>#', $text, $source, PREG_SET_ORDER);
		$text = @$source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all('#<a href="dl.php\?id=(.*?)" class="genmed">#', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
		$pos = strpos($text, '<span id="pp_');
		$text = substr($text, $pos);
		$pos = strpos($text, '<div id="pc_');
		$text = substr($text, 0, $pos);
		$text = preg_replace('/<span id="pp_.*?">/', '', $text);

		$text = str_replace('<div class="c-wrap">', '', $text);
		$text = str_replace('<div class="q-wrap">', '', $text);
		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('<div', '<span', $text);
		$text = str_replace('</div>', '</span>', $text);
		$text = str_replace('<a', '<span', $text);
		$text = str_replace('</a>', '</span>', $text);
		$text = str_replace('https://href.li/?', '', $text);

		$text = str_replace('<span class="post-hr">-</span>', "\n[hr]\n", $text);
		$text = str_replace('<span class="post-br"><br /></span>', "\n[br]\n", $text);
		$text = str_replace('<br />', "\n", $text);
		$text = preg_replace('/<var class="postImg postImgAligned img-(.*?)" title="([^<]*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
		$text = preg_replace('/<var class="postImg" title="([^<]*?)">&#10;<(?=\/)\/var>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
		$text = preg_replace('/<a href="([^<]*?)" data-rel="lightcase:myCollection:slideshow">.*?<\/a>/', '[th]$1[/th]', $text);
		$text = preg_replace('/<object.*?><param name="movie" value="([^<]*?)"><\/param>[\s\S]*?<\/object>/', '[youtube]$1[/youtube]', $text);
		$text = str_replace('youtube.com/v/', 'youtube.com/watch?v=', $text);
		$text = str_replace('&hl=ru_RU&fs=1&', '', $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<span class="post-b">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<span class="post-u">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<span class="post-i">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<span class="post-s">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
			$text = preg_replace('/<span class="post-sh">([^<]*?)<(?=\/)\/span>/', '[sh]$1[/sh]', $text);
			$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span href="([^<]*?)" class="postLink" rel="nofollow">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<span class="sp-wrap"><span class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/span><(?=\/)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			$text = preg_replace('/<span class="q">([^<]*?)<(?=\/)\/span>([^<]*?)<([^<]*?)\/span>/', "[quote]\n\\1\n[/quote]", $text);
			$text = preg_replace('/<span class="q" head="([^<]*?)">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
			$text = preg_replace('/<span class="c-body">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[code]\n\\1\n[/code]", $text);
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
			$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		}

		// Вставка плеера
		insert_video_player($text);

		//dump($text);
		// Убираем пустое пространство
		//$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);
		$text = strip_tags(html_entity_decode($text));
	}
	return $text;
}

function ddgroupclub($text, $mode = '')
{
	global $bb_cfg;

	if ($mode == 'title') {
		preg_match_all('#<title>([\s\S]*?) ::.*?</title>#', $text, $source, PREG_SET_ORDER);
		$text = @$source[0][1];
	} elseif ($mode == 'torrent') {
		preg_match_all('#<a href="dl.php\?id=(.*?)" class=".*?">#', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} /*
		    elseif($mode == 'torrent')
		    {
		    	preg_match_all ('#<a href="dl.php\?id=(.*?)" class="genmed">#', $text, $source, PREG_SET_ORDER);
			    $text = $source[0][1];
			}
			*/
	else {
		$pos = strpos($text, '<span id="pp_');
		$text = substr($text, $pos);
		$pos = strpos($text, '<div id="pc_');
		$text = substr($text, 0, $pos);
		$text = preg_replace('/<span id="pp_.*?">/', '', $text);
		$text = preg_replace('#<h3 class="sp-title">.*?</h3>#', '', $text);
		$text = str_replace('<div class="c-wrap">', '', $text);
		$text = str_replace('<div class="q-wrap">', '', $text);

		$text = preg_replace_callback('/<a href="\/cdn-cgi\/l\/email-protection" class="__cf_email__" data-cfemail="([a-z\d]*)">([^<]*)<\/a>/s', function ($matches) {
			return decodeEmailProtection($matches[1]);
		}, $text);

		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('<li>', "\n[*]", $text);
		$text = str_replace('<div', '<span', $text);
		$text = str_replace('</div>', '</span>', $text);
		$text = str_replace('<a', '<span', $text);
		$text = str_replace('</a>', '</span>', $text);
		$text = str_replace('https://href.li/?', '', $text);

		$text = str_replace('<span class="post-hr">-</span>', "\n[hr]\n", $text);
		$text = str_replace('<span class="post-br"><br /></span>', "\n[br]\n", $text);
		$text = str_replace('<br />', "\n", $text);
		$text = preg_replace('/<var class="postImg postImgAligned img-([^<]*?)" title="([^<]*?)">&#10;<\/var>/', "[img=\\1]\\2[/img]\n", $text);
		$text = preg_replace('/<var class="postImg" title="([^<]*?)">&#10;<(?=\/)\/var>/', '[img]$1[/img]', $text);
		$text = preg_replace('/<img class="smile" src=".*?" align="absmiddle" border="0" \/>/', '', $text);
		$text = preg_replace('/<span href="([^<]*?)".*? rel="topic" class="highslide">.*?<\/span>/', '[th]$1[/th]', $text);

		$text = preg_replace('/<span href=".*?" target="_blank"><img src=".\/ratings\/kinopoisk.php\?url=([^<]*?)" ><\/span>/', '[kp]$1[/kp]', $text);
		$text = preg_replace('/<span href=".*?" target="_blank"><img src=".\/ratings\/imdb.php\?url=([^<]*?)" ><\/span>/', '[imdb]$1[/imdb]', $text);

		$text = preg_replace('/<span style="width:.*?"><span style="overflow:.*?">([^<]*?)<\/span><\/span>/', '[scroll]$1[/scroll]', $text);
		$text = preg_replace('/<iframe.*?src="([^<]*?)" frameborder="0" allowfullscreen><\/iframe>/', '[youtube]https:$1[/youtube]', $text);
		$text = str_replace('youtube.com/v/', 'youtube.com/watch?v=', $text);
		$text = str_replace('&hl=ru_RU&fs=1&', '', $text);
		$text = str_replace('youtube.com/embed/', "youtube.com/watch?v=", $text);

		for ($i = 0; $i <= 20; $i++) {
			$text = preg_replace('/<span class="post-b">([^<]*?)<(?=\/)\/span>/', '[b]$1[/b]', $text);
			$text = preg_replace('/<span class="post-u">([^<]*?)<(?=\/)\/span>/', '[u]$1[/u]', $text);
			$text = preg_replace('/<span class="post-i">([^<]*?)<(?=\/)\/span>/', '[i]$1[/i]', $text);
			$text = preg_replace('/<span class="post-s">([^<]*?)<(?=\/)\/span>/', '[s]$1[/s]', $text);
			$text = preg_replace('/<span class="post-sh">([^<]*?)<(?=\/)\/span>/', '[sh]$1[/sh]', $text);
			$text = preg_replace('/<span class="post-d">([^<]*?)<(?=\/)\/span>/', '[d]$1[/d]', $text);
			$text = preg_replace('/<span style="font-size: ([^<]*?)px; line-height: normal;">([^<]*?)<(?=\/)\/span>/', "[size=\\1]\\2[/size]", $text);
			$text = preg_replace('/<span style="font-family: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[font=\"\\1\"]\\2[/font]", $text);
			$text = preg_replace('/<span class="post-align" style="text-align: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', "[align=\\1]\n\\2\n[/align]", $text);
			$text = preg_replace('/<span style="color: ([^<]*?);">([^<]*?)<(?=\/)\/span>/', '[color=$1]$2[/color]', $text);
			$text = preg_replace('/<span href="([^<]*?)" class="postLink">([^<]*?)<(?=\/)\/span>/', '[url=$1]$2[/url]', $text);
			$text = preg_replace('/<span class="sp-wrap"><span class="sp-body" title="([^<]*?)">([^<]*?)<(?=\/)\/span><(?=\/)\/span>/', "[spoiler=\"\\1\"]\n\\2\n[/spoiler]", $text);
			$text = preg_replace('/<span class="q">([^<]*?)<(?=\/)\/span>([^<]*?)<([^<]*?)\/span>/', "[quote]\n\\1\n[/quote]", $text);
			$text = preg_replace('/<span class="q" head="([^<]*?)">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[quote=\"\\1\"]\n\\2\n[/quote]", $text);
			$text = preg_replace('/<span class="c-body">([^<]*?)<(?=\/)\/span>([\s\S]*?)<([^<]*?)\/span>/', "[code]\n\\1\n[/code]", $text);
			$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru", $text);
			$text = preg_replace('/\[url=.*?multi-up.com.*?\].*?\[\/url\]/', "", $text);
		}
		// Убираем пустое пространство
		//$text = preg_replace('/([\r\n])[\s]+/is', "\\1", $text);
		$text = strip_tags(html_entity_decode($text));
		//dump($text);
	}
	return $text;
}

function xxxtor($text, $mode = false)
{
	if ($mode == 'title') {
		preg_match_all("#<h1>([\s\S]*?)</h1>#si", $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
		$text = preg_replace('/\[[\s\S]*?\.[\s\S]*? \/ [\s\S]*?\.[\s\S]*?\]/', '', $text);
		$text = preg_replace('/\[[\s\S]*?\.[\s\S]*?\]/', '', $text);
	} else if ($mode == 'torrent') {
		preg_match_all('#<a class="yellowBtn" href=".*?var=//(.*?)&.*?var2.*?</a>#', $text, $source, PREG_SET_ORDER);
		$text = $source[0][1];
	} else {
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

		$text = strip_tags($text);
		$text = html_entity_decode($text);
		//dump($text);
	}
	return $text;
}
