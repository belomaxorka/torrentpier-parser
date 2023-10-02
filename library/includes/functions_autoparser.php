<?php

if (!defined('BB_ROOT')) die(basename(__FILE__));

	    function nnmclub($text, $mode=false)
		{
			global $bb_cfg;
			$server_name = $bb_cfg['server_name'];
			$sitename = $bb_cfg['sitename'];
			if($mode == 'title')
			{
				preg_match_all ("#<a class=\"maintitle\" href=\"viewtopic.php\?t=.*?\">(.*?)</a>#", $text, $source, PREG_SET_ORDER);
			    $text = $source[0][1];
			}
		    elseif($mode == 'torrent')
		    {
		    	preg_match_all ('#<td colspan="3" class="gen".*?><b>([\s\S]*?).torrent</b></td>.*?<a href="download.php\?id=([\d]+)" rel="nofollow">.*?</a>#s', $text, $source, PREG_SET_ORDER);
			    $text = $source[0];
		    }
		    else
		    {
			if(preg_match_all("#.*?/reply-locked.gif#si", $text, $source, PREG_SET_ORDER))
			
			{

				$pos = strpos($text, '<td colspan="2"><div class="postbody"');
				$text = substr($text, $pos);
				$pos = strpos($text, '<tr><td colspan="2">');
				$text = substr($text, 0, $pos);

                $text = str_replace('<span class="postbody">', '', $text);
                $text = str_replace('<!--/spoiler-body-->', '', $text);
                $text = str_replace('<!--/spoiler-wrap-->', '', $text);
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

				for ($i=0; $i<=20; $i++)
				{
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
				/*	$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru/", $text);
					$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/.*?-[0-9]{4}-(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
					$text = preg_replace('#\[url=http.*?kinopoisk.ru/level/.*?/film/(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
					$text = preg_replace('#\[url=http.*?kinopoisk.ru/film/(.*?)/].*?\[\/url\]#', "[kp]https://www.kinopoisk.ru/film/$1[/kp]", $text);
					$text = preg_replace('#\[url=http.*?imdb.com/title/(.*?)/].*?\[\/url\]#', "[imdb]https://www.imdb.com/title/$1[/imdb]", $text);
					$text = preg_replace('#<a href="/?q=.*?w=title".*?>#', '', $text);
*/
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
				$text = strip_tags(html_entity_decode($text));
		}
		else 
		{
			preg_match_all ("#<a href=\"posting.php\?mode=quote&amp;p=(.*?)\" rel=\"nofollow\"><img src=\".*?icon_quote.gif\".*?border=\"0\" class=\"pims\"></a>#", $text, $id, PREG_SET_ORDER);
				$post_id = $id[0][1];
				

				$curl = new \Dinke\CurlHttpClient;

				$url = "https://nnmclub.to/forum/posting.php?mode=quote&p=$post_id";
				$curl->setUserAgent("Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1");
				$curl->storeCookies(COOKIES_PARS_DIR . '/nnm_cookie.txt');
				
				/*$source = $curl->fetchUrl($url);

			if(preg_match_all('#class="thHead" nowrap="nowrap"#si', $text, $source, PREG_SET_ORDER))
			{*/
				$submit_url = "https://nnmclub.to/forum/login.php";
				$submit_vars = array (
					'username' => $bb_cfg['auth']['nnmclub']['login'],
					'password' => $bb_cfg['auth']['nnmclub']['pass'],
					'login'    => true,
				);

				$curl->sendPostData($submit_url, $submit_vars);
				
			/*}
		else 
		{*/
				$source = $curl->fetchUrl($url);
				$source  = iconv('windows-1251', 'UTF-8', $source);
			    $text = $source;
				//dump($text);
				preg_match_all ("#<textarea.*?\">\[\quote=\".*?\";p=\".*?\"\]([\s\S]*?)\[/quote\]</textarea>#", $text, $source, PREG_SET_ORDER);

				if (!$source)
				{
					//meta_refresh('release.php', '8');
					bb_die('Куки не найдены, попробуйте ещё раз, со второго раза точно получится');
				}

				$text = $source[0][1];

		//}
		//var_dump($text );
				
				
				
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
				
				$text = preg_replace('/\[url=http.*?nnm.*?\/forum\/viewtopic.php.*?\].*?\[\/url]/', '', $text);

				$text = preg_replace('/\[hide=([\s\S]*?)\]/', '[spoiler="$1"]', $text);
				$text = str_replace('[/hide]', '[/spoiler]', $text);
				$text = str_replace('[brc]', "", $text);
				$text = preg_replace('/\[imdb\]tt([\d]+)\[\/imdb\]/', '', $text);
				$text = preg_replace('/\[kp\]([\d]+)\[\/kp\]/', '', $text);
                $text = str_replace('http://assets.nnm-club.ws/forum/image.php?link=', '', $text);
                $text = str_replace('http://nnmassets.cf/forum/image.php?link=', '', $text);

                $text = str_replace('http://assets.ipv6.nnm-club.ws/forum/image.php?link=', '', $text);
                $text = preg_replace('/hs.expand(this,{slideshowGroup:.*?})"/', '', $text);
                $text = str_replace('forum/images/channel/sample_light_nnm.png', "https://$server_name/data/pictures/2/24.jpg", $text);
                $text = str_replace('https://href.li/?', '', $text);
                $text = str_replace('http://nnmclub.ch/forum/image.php?link=', '', $text);
				$text = preg_replace('#\[url=mailto.*?].*?\[\/url\]#', "$1", $text);
			//	$text = preg_replace('/http:(.*?)kinopoisk.ru/', "https:$1kinopoisk.ru/", $text);
		}
			}
			
			return $text;
		}