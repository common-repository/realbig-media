<?php

if (!defined("ABSPATH")) { exit;}

try {
	// rss init begin
	if (!function_exists('RFWP_rssInit')) {
		function RFWP_rssInit() {
			$posts = [];
			$rb_rssFeedUrls = [];
			$rssPartsCount = 1;
			$rssOptions = RFWP_rssOptionsGet();
			$GLOBALS['rb_rssDivideOptions'] = [];

			if (!empty($rssOptions))
			{
				$postTypes = $rssOptions['typesPost'];
				$feedName = $rssOptions['name'];
				add_feed($feedName, 'RFWP_rssCreate');
				array_push($rb_rssFeedUrls, $feedName);
			}

			if (!empty($postTypes)) {
				$tax_query = RFWP_rss_taxonomy_get($rssOptions);
				$postTypes = explode(';', $postTypes);
				$posts = get_posts([
					'numberposts' => $rssOptions['pagesCount'],
					'post_type' => $postTypes,
					'tax_query' => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'fields' => ['ID'],
				]);
			}

			if (!empty($posts)) {
				$GLOBALS['rb_rssTurboAds'] = RFWP_getTurboAds();
				$rssDividedPosts = RFWP_rssDivine($posts, $rssOptions);
				$GLOBALS['rb_rssDivideOptions']['posts'] = $rssDividedPosts;
				$GLOBALS['rb_rssDivideOptions']['iteration'] = 0;
				$rssOptions['rssPartsSeparated'] = intval($rssOptions['rssPartsSeparated']);
				if ($rssOptions['rssPartsSeparated']  < 1) {
					$rssOptions['rssPartsSeparated'] = 1;
				}
				if (!empty($rssOptions['divide'])&&!($rssOptions['rssPartsSeparated'] >= count($posts))) {
					$rssPartsCount = count($posts)/$rssOptions['rssPartsSeparated'];
					$rssPartsCount = ceil($rssPartsCount);
					$feed = [];
					for ($cou = 0; $cou < $rssPartsCount; $cou++) {
						$newFeedName = '';
						if ($cou > 0) {
							if (get_option('permalink_structure')) {
								$feedPage = '/?paged='.($cou+1);
							} else {
								$feedPage = '&paged='.($cou+1);
							}
							$newFeedName = $feedName.$feedPage;
							add_feed($newFeedName, 'RFWP_rssCreate');
							array_push($rb_rssFeedUrls, $newFeedName);
						}
					}
				}
			}
			if (!empty($rb_rssFeedUrls)) {
				$GLOBALS['rb_rssFeedUrls'] = $rb_rssFeedUrls;
			}

			global $wp_rewrite;
			$wp_rewrite->flush_rules(false);
		}
	}
	// rss init end
	if (!function_exists('RFWP_getTurboAds')) {
		function RFWP_getTurboAds() {
			if (!isset($GLOBALS['rb_turboAds'])) {
				global $wpdb;
				global $wpPrefix;

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$rb_turboAds = $wpdb->get_results("SELECT * FROM `{$wpPrefix}realbig_turbo_ads`", ARRAY_A);
				$GLOBALS['rb_turboAds'] = $rb_turboAds;
			} else {
				$rb_turboAds = $GLOBALS['rb_turboAds'];
			}
			return $rb_turboAds;
		}
	}
	// search for hardcoded
    if (!function_exists('RFWP_rssContentFiltrate')) {
	    function RFWP_rssContentFiltrate($content, $rssOptions, $postId) {
		    $content = RFWP_rss_build_template($content, $rssOptions, $postId);
		    $content = RFWP_strip_attributes($content,array('src'));
		    $content = RFWP_rss_strip_shortcodes($content, $rssOptions);
		    $content = do_shortcode($content);

		    if (!empty($rssOptions['filterTagsWithoutContent'])&&!empty($rssOptions['filterTagsWithoutContentField'])) {
			    $content = RFWP_rss_strip_tags_without_content($content, $rssOptions['filterTagsWithoutContentField']);
            }

		    if (!empty($rssOptions['filterTagsWithContent'])&&!empty($rssOptions['filterTagsWithContentField'])) {
			    $content = RFWP_rss_strip_tags_with_content($content, $rssOptions['filterTagsWithContentField'], true);
            }

		    $content = wpautop($content);

		    //удаляем unicode-символы (как невалидные в rss)
		    $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);

		    //deleting "&"
//		    $pattern = "\&";
//		    $replacement = "and";
//		    $content = preg_replace($pattern, $replacement, $content);

		    //удаляем разметку движка при использовании шорткода с подписью [caption] (в html4 темах - classic editor)
		    $pattern = "/<div id=\"attachment(.*?)>(.*?)<img (.*?) \/>(.*?)<\/p>\n<p id=\"caption-attachment(.*?)\">(.*?)<\/p>\n<\/div>/i";
		    $replacement = '<img data-caption="$6" $3 />';
		    $content = preg_replace($pattern, $replacement, $content);
		    //разметка описания на случай, если тег <div> удаляется в настройках плагина
		    $pattern = "/<p><img(.*?) \/><\/p>\n<p id=\"caption-attachment(.*?)\">(.*?)<\/p>/i";
		    $replacement = '<img data-caption="$3"$1 />';
		    $content = preg_replace($pattern, $replacement, $content);

		    //удаляем разметку движка при использовании шорткода с подписью [caption] (в html5 темах - classic editor)
		    $pattern = "/<figure id=\"attachment(.*?)\"(.*?)>(.*?)<img (.*?) \/>(.*?)<figcaption id=\"caption-attachment(.*?)\">(.*?)<\/figcaption><\/figure>/i";
		    $replacement = '<img data-caption="$7" $4 />';
		    $content = preg_replace($pattern, $replacement, $content);

		    //удаляем <figure>, если они изначально присутствуют в контенте записи (с указанным caption - gutenberg)
		    $pattern = "/<figure(.*?)>(.*?)<img src=\"(.*?)\" \/>(.*?)<figcaption>(.*?)<\/figcaption><\/figure>/i";
		    $replacement = '<img data-caption="$5" src="$3" />';
		    $content = preg_replace($pattern, $replacement, $content);

		    //удаляем <figure>, если они изначально присутствуют в контенте записи (без caption - gutenberg)
		    $pattern = "/<figure(.*?)>(.*?)<img(.*?)>(.*?)<\/figure>/i";
		    $replacement = '<img$3>';
		    $content = preg_replace($pattern, $replacement, $content);

		    //удаляем <figure> вокруг всех элементов (яндекс такое не понимает)
		    $pattern = "/<figure(.*?)>/i";
		    $replacement = '';
		    $content = preg_replace($pattern, $replacement, $content);
		    $pattern = "/<\/figure>/i";
		    $replacement = '';
		    $content = preg_replace($pattern, $replacement, $content);
		    $pattern = "/<figcaption>(.*?)<\/figcaption>/i";
		    $replacement = '';
		    $content = preg_replace($pattern, $replacement, $content);

		    //преобразуем iframe с видео
		    $pattern = "/<iframe title=\"(.*?)\"(.*?) allow=\"(.*?)\"(.*?)><\/iframe>/i";
		    $replacement = '<iframe$2 allowfullscreen="true"></iframe>';
		    $content = preg_replace($pattern, $replacement, $content);

		    //удаляем <p> у отдельно стоящих изображений
		    $pattern = "/<p><img(.*?)><\/p>/i";
		    $replacement = '<img$1>';
		    $content = preg_replace($pattern, $replacement, $content);

		    //добавляем data-caption если его вообще нет в теге img
		    $pattern = "/<img(?!([^>]*\b)data-caption=)([^>]*?)>/i";
		    $replacement = '<img data-caption=""$1$2>';
		    $content = preg_replace( $pattern, $replacement, $content );

		    //обрабатываем img теги и оборачиваем их тегами figure
		    if ($rssOptions['ImageDesc'] == 'enable') {
			    //если описания нет
			    $pattern = "/<img data-caption=\"\" src=\"(.*?)\" \/>/i";
			    $replacement = '<figure><img src="$1" /></figure>';
			    $content = preg_replace($pattern, $replacement, $content);
			    //если описание есть
			    $pattern = "/<img data-caption=\"(.*?)\" src=\"(.*?)\" \/>/i";
			    $replacement = '<figure><img src="$2" /><figcaption>$1</figcaption></figure>';
			    $content = preg_replace($pattern, $replacement, $content);
		    } else {
			    $pattern = "/<img data-caption=\"(.*?)\" src=\"(.*?)\" \/>/i";
			    $replacement = '<figure><img src="$2" /></figure>';
			    $content = preg_replace($pattern, $replacement, $content);
		    }

		    $purl = plugins_url('', __FILE__);

		    //формируем video для mp4 файлов согласно документации яндекса (гутенберг)
		    $pattern = "/<video(.*?)src=\"(.*?).mp4\"><\/video>/i";
		    $replacement = '<figure><video><source src="$2.mp4" type="video/mp4" /></video><img src="'.$purl.'/img/video.png'.'" /></figure>';
		    $content = preg_replace($pattern, $replacement, $content);

		    //формируем video для mp4 файлов согласно документации яндекса (классический редактор)
		    $content = str_replace('<!--[if lt IE 9]><script>document.createElement(\'video\');</script><![endif]-->', '', $content);
		    $pattern = "/<video class=\"wp-video-shortcode\"(.*?)><source(.*?)src=\"(.*?).mp4(.*?)\"(.*?)\/>(.*?)<\/video>/i";
		    $replacement = '<figure><video><source src="$3.mp4" type="video/mp4" /></video><img src="'.$purl.'/img/video.png'.'" /></figure>';
		    $content = preg_replace($pattern, $replacement, $content);

		    //add "formaction" in buttons
		    $pattern = "~\<button([^>]*?)\>~i";
		    $replacement = '<button formaction="tel:+38(123)456-78-90" $1>';
		    $content = preg_replace($pattern, $replacement, $content);

		    //удаляем картинки из контента, если их больше 50 уникальных (ограничение яндекс.турбо)
		    if (preg_match_all("/<figure><img(.*?)>(.*?)<\/figure>/i", $content, $res)) {
			    $i = 0;
			    if (!empty($rssOptions['blockRelated']) && !empty($rssOptions['blockRelatedCount']) && empty($rssOptions['blockRelatedUnstopable'])) {
				    $i = $rssOptions['blockRelatedCount'];
                }
			    if (!empty($rssOptions['thumbnails'])&&has_post_thumbnail($postId)) {
				    $i++;
                }
			    $final = array();
			    foreach ($res[0] as $r) {
				    if (! in_array($r, $final)) {$i++;}
				    if ($i > 50 && ! in_array($r, $final)) {{}
					    $content = str_replace($r, '', $content);
				    }
				    if (! in_array($r, $final)) {$final[] = $r;}
			    }
		    }

		    if (!empty($rssOptions['filterContent'])&&!empty($rssOptions['filterContentField'])) {
			    $textAr = explode("\n", str_replace(array("\r\n", "\r"), "\n", $rssOptions['filterContentField']));
			    foreach ($textAr as $line) {
				    $line = stripcslashes($line);
				    $content = str_replace($line, '', $content);
			    }
		    }

		    $content = RFWP_rss_do_gallery($content);
		    if (!empty($rssOptions['toc'])) {
			    $content = RFWP_rssTocAdd($content, $rssOptions, $postId);
		    }

		    return $content;
	    }
    }
    //функция преобразования стандартных галерей движка в турбо-галереи end
    //функция преобразования стандартных галерей движка в турбо-галереи в гутенберге begin
	if (!function_exists('RFWP_rss_do_gallery')) {
		function RFWP_rss_do_gallery( $content ) {

			//удаляем ul разметку галерей в гутенберге (wordpress 5.3+)
			$pattern = "/<ul class=\"blocks-gallery-grid(.*?)>(.*?)<\/ul>/s";
			$replacement = '<div data-block="gallery">$2</div>';
			$content = preg_replace($pattern, $replacement, $content);

			//удаляем ul разметку галерей в гутенберге (wordpress 5.2+)
			$pattern = "/<ul class=\"wp-block-gallery(.*?)>(.*?)<\/ul>/s";
			$replacement = '<div data-block="gallery">$2</div>';
			$content = preg_replace($pattern, $replacement, $content);

			//удаляем li разметку галерей в гутенберге
			$pattern = "/<li class=\"blocks-gallery-item\">\n<figure><img src=\"(.*?)\" \/>(.*?)<\/figure>\n<\/li>/i";
			$replacement = '<img src="$1"/>';
			$content = preg_replace($pattern, $replacement, $content);

			return $content;
		}
    }
    //функция преобразования стандартных галерей движка в турбо-галереи в гутенберге end
	if (!function_exists('RFWP_strip_attributes')) {
		function RFWP_strip_attributes( $s, $allowedattr = array() ) {
			if (preg_match_all("/<img[^>]*\\s([^>]*)\\/*>/msiU", $s, $res, PREG_SET_ORDER)) {
				foreach ($res as $r) {
					$tag = $r[0];
					$attrs = array();
					preg_match_all("/\\s.*=(['\"]).*\\1/msiU", " " . $r[1], $split, PREG_SET_ORDER);
					foreach ($split as $spl) {
						$attrs[] = $spl[0];
					}
					$newattrs = array();
					foreach ($attrs as $a) {
						$tmp = explode("=", $a);
						if (trim($a) != "" && (!isset($tmp[1]) || (trim($tmp[0]) != "" && !in_array(strtolower(trim($tmp[0])), $allowedattr)))) {

						} else {
							$newattrs[] = $a;
						}
					}

					//сортировка чтобы alt был раньше src
					sort($newattrs);
					reset($newattrs);

					$attrs = implode(" ", $newattrs);
					$rpl = str_replace($r[1], $attrs, $tag);
					//заменяем одинарные кавычки на двойные
					$rpl = str_replace("'", "\"", $rpl);
					//добавляем закрывающий символ / если он отсутствует
					$rpl = str_replace("\">", "\" />", $rpl);
					//добавляем пробел перед закрывающим символом /
					$rpl = str_replace("\"/>", "\" />", $rpl);
					//удаляем двойные пробелы
					$rpl = str_replace("  ", " ", $rpl);

					$s = str_replace($tag, $rpl, $s);
				}
			}
			return $s;
		}
	}
	if (!function_exists('RFWP_rss_remove_emoji')) {
		function RFWP_rss_remove_emoji($text) {
			$clean_text = '';

			// Match Emoticons
			$clean_text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);

			// Match Miscellaneous Symbols and Pictographs
			$clean_text = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $clean_text);

			// Match Transport And Map Symbols
			$clean_text = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $clean_text);

			// Match Miscellaneous Symbols
			$clean_text = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $clean_text);

			// Match Dingbats
			$clean_text = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $clean_text);

			// Match Flags
			$clean_text = preg_replace('/[\x{1F1E6}-\x{1F1FF}]/u', '', $clean_text);

			// Others
			$clean_text = preg_replace('/[\x{1F910}-\x{1F95E}]/u', '', $clean_text);

			$clean_text = preg_replace('/[\x{1F980}-\x{1F991}]/u', '', $clean_text);
			$clean_text = preg_replace('/[\x{1F9C0}]/u', '', $clean_text);
			$clean_text = preg_replace('/[\x{1F9F9}]/u', '', $clean_text);

			// Unicode
			$clean_text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean_text);

			return $clean_text;
		}
	}
	if (!function_exists('RFWP_rss_cut_by_words')) {
		function RFWP_rss_cut_by_words( $maxlen, $text ) {
			$len = (mb_strlen($text) > $maxlen)? mb_strripos(mb_substr($text, 0, $maxlen), ' ') : $maxlen;
			$cutStr = mb_substr($text, 0, $len);
			$temp = (mb_strlen($text) > $maxlen)? $cutStr. '...' : $cutStr;
			return $temp;
		}
	}
	if (!function_exists('RFWP_rssOptionsGet')) {
	    function RFWP_rssOptionsGet() {
	        if (!empty($GLOBALS['rssOptions'])) {
	            $rssOptions = $GLOBALS['rssOptions'];
            } else {
		        $rssOptions = [];
		        $rssOptions['contentType'] = 'application/rss+xml';
		        $rssOptions['charset'] = 'UTF-8';
		        $rssOptions['analytics'] = null;
		        $rssOptions['adNetwork'] = null;
		        $rssOptions['version'] = '0.5';
		        // 1st part
		        $rssOptions['name'] = 'rb_turbo_rss';
		        $rssOptions['title'] = 'test_title';
		        $rssOptions['url'] = RFWP_getDomain();
		        $rssOptions['description'] = 'test_desc';
		        $rssOptions['lang'] = 'RU';
		        $rssOptions['pagesCount'] = 5;
		        $rssOptions['divide'] = 0;
		        $rssOptions['rssPartsSeparated'] = 5;
		        $rssOptions['selectiveOff'] = 0;
		        $rssOptions['selectiveOffTracking'] = 0;
		        $rssOptions['selectiveOffField'] = '';
		        $rssOptions['onTurbo'] = 'true';
		        $rssOptions['onOffProtocol'] = 'default';
		        // 2nd part
		        $rssOptions['PostHtml'] = 0;
		        $rssOptions['PostDate'] = 0;
		        $rssOptions['PostDateType'] = 'create';
		        $rssOptions['PostExcerpt'] = 0;
		        $rssOptions['PostTitle'] = false;
		        $rssOptions['SeoPlugin'] = 'yoast_seo';
		        $rssOptions['Thumbnails'] = 0;
		        $rssOptions['ThumbnailsSize'] = 'thumbnail';
		        $rssOptions['PostAuthor'] = 'disable';
		        $rssOptions['PostAuthorDirect'] = 'local_test_author';
		        $rssOptions['ImageDesc'] = 'disable';
		        $rssOptions['toc'] = 0;
		        $rssOptions['tocPostTypes'] = [];
		        $rssOptions['tocTitleText'] = 'default_toc_title';
		        $rssOptions['tocPosition'] = 'postBegin';
		        $rssOptions['tocTitlesMin'] = 2;
		        $rssOptions['tocTitlesLevels'] = false;
		        // 3rd part
		        $rssOptions['menu']= 'not_use';
		        $rssOptions['blockShare']= 0;
		        $rssOptions['blockShareSocials']= false;
		        $rssOptions['blockShareOrder']= "";
		        $rssOptions['blockFeedback']= 0;
		        $rssOptions['blockFeedbackPosition']= 'left';
		        $rssOptions['blockFeedbackPositionPlace']= 'begin';
		        $rssOptions['blockFeedbackPositionTitle']= 'pos_title';
		        $rssOptions['blockFeedbackButton']= false;
		        $rssOptions['blockFeedbackButtonOrder']= "";
		        $rssOptions['blockFeedbackButtonContacts']= 'empty';
		        $rssOptions['blockFeedbackButtonContactsCall']= false;
		        $rssOptions['blockFeedbackButtonContactsCallbackEmail']= false;
		        $rssOptions['blockFeedbackButtonContactsCallbackOrganizationName']= false;
		        $rssOptions['blockFeedbackButtonContactsCallbackTermsOfUse']= false;
		        $rssOptions['blockFeedbackButtonContactsChat']= false;
		        $rssOptions['blockFeedbackButtonContactsMail']= false;
		        $rssOptions['blockFeedbackButtonContactsVkontakte']= false;
		        $rssOptions['blockFeedbackButtonContactsOdnoklassniki']= false;
		        $rssOptions['blockFeedbackButtonContactsTwitter']= false;
		        $rssOptions['blockFeedbackButtonContactsFacebook']= false;
		        $rssOptions['blockFeedbackButtonContactsViber']= false;
		        $rssOptions['blockFeedbackButtonContactsWhatsapp']= false;
		        $rssOptions['blockFeedbackButtonContactsTelegram']= false;
		        $rssOptions['blockComments']= 0;
		        $rssOptions['blockCommentsAvatars']= 0;
		        $rssOptions['blockCommentsCount']= 20;
		        $rssOptions['blockCommentsSort']= 'new_in_begin';
		        $rssOptions['blockCommentsDate']= 0;
		        $rssOptions['blockCommentsTree']= 0;
		        $rssOptions['blockRelated']= 0;
		        $rssOptions['blockRelatedCount']= 5;
		        $rssOptions['blockRelatedDateLimitation']= 72;
		        $rssOptions['blockRelatedThumb']= false;
		        $rssOptions['blockRelatedUnstopable']= 0;
		        $rssOptions['blockRelatedCaching']= 0;
		        $rssOptions['blockRelatedCachelifetime']= false;
		        $rssOptions['blockRating']= 0;
		        $rssOptions['blockRatingFrom']= 1;
		        $rssOptions['blockRatingTo']= 5;
		        $rssOptions['blockSearch']= 0;
		        $rssOptions['blockSearchDefaultText']= false;
		        $rssOptions['blockSearchPosition']= 'postBegin';
		        // 4th part
		        $rssOptions['couYandexMetrics']= '';
		        $rssOptions['couLiveInternet']= '';
		        $rssOptions['couGoogleAnalytics']= '';
		        // 5th part
		        $rssOptions['typesPost']= 'post;pro_tag';
		        $rssOptions['typesIncludes']= 'exclude';
		        $rssOptions['typesTaxExcludes']= '';
		        $rssOptions['typesTaxIncludes']= '';
		        $rssOptions['typesInAdminCol']= 0;
		        // 6th part
		        $rssOptions['filterSc']= 0;
		        $rssOptions['filterScField']= '';
		        $rssOptions['filterTagsWithoutContent']= 0;
		        $rssOptions['filterTagsWithoutContentField']= '';
		        $rssOptions['filterTagsWithContent']= 0;
		        $rssOptions['filterTagsWithContentField']= '';
		        $rssOptions['filterContent']= 0;
		        $rssOptions['filterContentField']= '';
		        // 7th part
		        $rssOptions['template-post']= '';
		        $rssOptions['template-page']= '';
		        $rssOptions['template-pro_tag']= '';

		        $namesMap = [
			        // 1st part
			        'name' => 'feedName',
			        'title' => 'feedTitle',
			        'url' => 'feedUrl',
			        'description' => 'feedDescription',
			        'lang' => 'feedLanguage',
			        'pagesCount' => 'feedPostCount',
			        'divide' => 'feedSeparate',
			        'rssPartsSeparated' => 'feedSeparateCount',
			        'selectiveOff' => 'feedSelectiveOff',
			        'selectiveOffTracking' => 'feedSelectiveOffTracking',
			        'selectiveOffField' => 'feedSelectiveOffField',
			        'onTurbo' => 'feedOnOff',
			        'onOffProtocol' => 'feedOnOffProtocol',
			        // 2nd part
			        'PostHtml' => 'feedPostHtml',
			        'PostDate' => 'feedPostDate',
			        'PostDateType' => 'feedPostDateType',
			        'PostExcerpt' => 'feedPostExcerpt',
			        'PostTitle' => 'feedPostTitle',
			        'SeoPlugin' => 'feedSeoPlugin',
			        'Thumbnails' => 'feedThumbnails',
			        'ThumbnailsSize' => 'feedThumbnailsSize',
			        'PostAuthor' => 'feedPostAuthor',
			        'PostAuthorDirect' => 'feedPostAuthorDirect',
			        'ImageDesc' => 'feedImageDesc',
			        'toc' => 'feedToc',
			        'tocPostTypes' => 'feedTocPostTypes',
			        'tocTitleText' => 'feedTocTitleText',
			        'tocPosition' => 'feedTocPosition',
			        'tocTitlesMin' => 'feedTocTitlesMin',
			        'tocTitlesLevels' => 'feedTocTitlesLevels',
			        // 3rd part
			        'menu' => 'feedMenu',
			        'blockShare' => 'feedBlockShare',
			        'blockShareSocials' => 'feedBlockShareSocials',
			        'blockShareOrder' => 'feedBlockShareOrder',
			        'blockFeedback' => 'feedBlockFeedback',
			        'blockFeedbackPosition' => 'feedBlockFeedbackPosition',
			        'blockFeedbackPositionPlace' => 'feedBlockFeedbackPositionPlace',
			        'blockFeedbackPositionTitle' => 'feedBlockFeedbackPositionTitle',
			        'blockFeedbackButton' => 'feedBlockFeedbackButton',
			        'blockFeedbackButtonOrder' => 'feedBlockFeedbackButtonOrder',
			        'blockFeedbackButtonContacts' => 'feedBlockFeedbackButtonContacts',
			        'blockFeedbackButtonContactsCall' => 'feedBlockFeedbackButtonContactsCall',
			        'blockFeedbackButtonContactsCallbackEmail' => 'feedBlockFeedbackButtonContactsCallbackEmail',
			        'blockFeedbackButtonContactsCallbackOrganizationName' => 'feedBlockFeedbackButtonContactsCallbackOrganizationName',
			        'blockFeedbackButtonContactsCallbackTermsOfUse' => 'feedBlockFeedbackButtonContactsCallbackTermsOfUse',
			        'blockFeedbackButtonContactsChat' => 'feedBlockFeedbackButtonContactsChat',
			        'blockFeedbackButtonContactsMail' => 'feedBlockFeedbackButtonContactsMail',
			        'blockFeedbackButtonContactsVkontakte' => 'feedBlockFeedbackButtonContactsVkontakte',
			        'blockFeedbackButtonContactsOdnoklassniki' => 'feedBlockFeedbackButtonContactsOdnoklassniki',
			        'blockFeedbackButtonContactsTwitter' => 'feedBlockFeedbackButtonContactsTwitter',
			        'blockFeedbackButtonContactsFacebook' => 'feedBlockFeedbackButtonContactsFacebook',
			        'blockFeedbackButtonContactsViber' => 'feedBlockFeedbackButtonContactsViber',
			        'blockFeedbackButtonContactsWhatsapp' => 'feedBlockFeedbackButtonContactsWhatsapp',
			        'blockFeedbackButtonContactsTelegram' => 'feedBlockFeedbackButtonContactsTelegram',
			        'blockComments' => 'feedBlockComments',
			        'blockCommentsAvatars' => 'feedBlockCommentsAvatars',
			        'blockCommentsCount' => 'feedBlockCommentsCount',
			        'blockCommentsSort' => 'feedBlockCommentsSort',
			        'blockCommentsDate' => 'feedBlockCommentsDate',
			        'blockCommentsTree' => 'feedBlockCommentsTree',
			        'blockRelated' => 'feedBlockRelated',
			        'blockRelatedCount' => 'feedBlockRelatedCount',
			        'blockRelatedDateLimitation' => 'feedBlockRelatedDateLimitation',
			        'blockRelatedThumb' => 'feedBlockRelatedThumb',
			        'blockRelatedUnstopable' => 'feedBlockRelatedUnstopable',
			        'blockRelatedCaching' => 'feedBlockRelatedCaching',
			        'blockRelatedCachelifetime' => 'feedBlockRelatedCachelifetime',
			        'blockRating' => 'feedBlockRating',
			        'blockRatingFrom' => 'feedBlockRatingFrom',
			        'blockRatingTo' => 'feedBlockRatingTo',
			        'blockSearch' => 'feedBlockSearch',
			        'blockSearchDefaultText' => 'feedBlockSearchDefaultText',
			        'blockSearchPosition' => 'feedBlockSearchPosition',
			        // 4th part
			        'couYandexMetrics' => 'feedCouYandexMetrics',
			        'couLiveInternet' => 'feedCouLiveInternet',
			        'couGoogleAnalytics' => 'feedCouGoogleAnalytics',
			        // 5th part
			        'typesPost' => 'feedTypesPost',
			        'typesIncludes' => 'feedTypesIncludes',
			        'typesTaxExcludes' => 'feedTypesTaxExcludes',
			        'typesTaxIncludes' => 'feedTypesTaxIncludes',
			        'typesInAdminCol' => 'feedTypesInAdminCol',
			        // 6th part
			        'filterSc' => 'feedFilterSc',
			        'filterScField' => 'feedFilterScField',
			        'filterTagsWithoutContent' => 'feedFilterTagsWithoutContent',
			        'filterTagsWithoutContentField' => 'feedFilterTagsWithoutContentField',
			        'filterTagsWithContent' => 'feedFilterTagsWithContent',
			        'filterTagsWithContentField' => 'feedFilterTagsWithContentField',
			        'filterContent' => 'feedFilterContent',
			        'filterContentField' => 'feedFilterContentField',
			        // 7th par,
			        'template-post'=> 'feedTemplatePost',
			        'template-page'=> 'feedTemplatePage',
			        'template-pro_tag'=> 'feedTemplateProTag',
		        ];

		        $rssOptionsGet = get_option('rb_TurboRssOptions');

		        if (!empty($rssOptionsGet)) {
		            if (is_string($rssOptionsGet)) {
			            $rssOptionsGet = json_decode($rssOptionsGet, true);
                    }

			        if (!empty($rssOptionsGet)) {
				        foreach ($namesMap AS $k => $item) {
					        if (isset($rssOptionsGet[$item])) {
					            switch ($item) {
                                    case 'feedOnOff':
                                        $localValue = $rssOptionsGet[$item];
	                                    $localValue = intval($rssOptionsGet[$item]);
	                                    if (!empty($localValue)) {
		                                    $localValue = 'false';
                                        } else {
		                                    $localValue = 'true';
                                        }

	                                    $rssOptions[$k] = $localValue;
                                        break;
                                    case 'feedTocPostTypes':
                                        $localValue = explode(';', $rssOptionsGet[$item]);
                                        $localValue = array_diff($localValue, ['']);
                                        $rssOptions[$k] = $localValue;
                                        break;
                                    default:
	                                    $rssOptions[$k] = $rssOptionsGet[$item];
                                        break;
                                }
					        }
				        }
				        unset($k,$item);
			        }
		        }

		        if (empty($rssOptionsGet)) {
			        $rssOptions = [];
                }

		        $GLOBALS['rssOptions'] = $rssOptions;
            }

		    return $rssOptions;
	    }
    }
	if (!function_exists('RFWP_rssDivine')) {
		function RFWP_rssDivine($posts, $rssOptions) {
			$rssDivided = [];
			if (empty($rssOptions['divide'])||(!empty($rssOptions['divide'])&&(count($posts) <= $rssOptions['rssPartsSeparated']))) {
				$rssDivided[0] = $posts;
			} else {
				$divideCounter = 0;
				$divideCurrent = 0;

				$divideMax = intval($rssOptions['rssPartsSeparated']);
				$rssDivided[$divideCurrent] = [];
				foreach ($posts AS $k => $item) {
					if ($divideCounter >= $divideMax) {
						$divideCurrent++;
						$rssDivided[$divideCurrent] = [];
						$divideMax = $divideMax+intval($rssOptions['rssPartsSeparated']);
					}
					array_push($rssDivided[$divideCurrent], $item);
					$divideCounter++;
				}
				unset($k,$item);
			}
		    return $rssDivided;
        }
	}
	if (!function_exists('RFWP_rssPostsCleaning')) {
		function RFWP_rssPostsCleaning($rssPosts, $rssOptions) {
			$arrayColumn = array_column($rssPosts, 'post_author');
			$getAuthors = get_users(['include'=>$arrayColumn]);
			foreach ($rssPosts AS $k1 => $item1) {
				foreach ($getAuthors AS $k => $item) {
					if ($item->ID == $item1->post_author) {
						$rssPosts[$k1]->post_author_name = $item->data->display_name;
					}
				}
				unset($k,$item);
				if (empty($rssPosts[$k1]->post_author_name)) {
					$rssPosts[$k1]->post_author_name = 'No name';
				}
				$contentPost = $rssPosts[$k1]->post_content;
//				if (!empty($contentPost)) {
//					$contentPost = RFWP_rss_turbo_ads_insert($contentPost);
//                }
				$excerpt = '';
				if (!empty($rssOptions['PostExcerpt'])&&!empty(!empty($rssPosts[$k1]->post_excerpt))) {
					$excerpt = '<p>'.$rssPosts[$k1]->post_excerpt.'</p>';
				}
				$contentPost = $excerpt.$contentPost;
				if (!empty($contentPost)) {
					$contentPost = RFWP_rssContentFiltrate($contentPost, $rssOptions, $rssPosts[$k1]->ID);
					if (!empty($contentPost)) {
						$contentPost = RFWP_rss_turbo_ads_insert($contentPost);
						$rssPosts[$k1]->post_content = $contentPost;
					}
				}
				if (!empty($item1->guid)) {
					$rssPosts[$k1]->guid = RFWP_filter_permalink_rss($item1->guid);
                }
			}
			unset($k1,$item1);

		    return $rssPosts;
        }
    }
	if (!function_exists('RFWP_rssTocAdd')) {
		function RFWP_rssTocAdd($content,$rssOptions,$postId) {
//			if ( ! is_feed($rssOptions['ytrssname']) )
//				return $content;

			$types = $rssOptions['tocPostTypes'];

			if (!in_array(get_post_type($postId), $types)) {
				return $content;
			}

			//подключение файла с классом Kama_Contents begin
			if (!class_exists('Kama_Contents')) {
				require_once plugin_dir_path(__FILE__) . 'class-Kama_Contents.php';
			}
			//подключение файла с классом Kama_Contents end

			$hLevels = [];
			if ( ! empty($rssOptions['tocTitlesLevels'])) {
				$hLevels = explode(';', $rssOptions['tocTitlesLevels']);
			}

			$hArray    = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
			$selectors = array();
			if ( ! empty($hLevels)) {
				foreach ($hLevels AS $item) {
					if (in_array($item, $hArray)) {
						array_push($selectors, $item);
					}
				}
				unset($item);
			}
//			if ($rssOptions['yttoch1']=='enabled'){array_push($selectors, 'h1');}
//			if ($rssOptions['yttoch2']=='enabled'){array_push($selectors, 'h2');}
//			if ($rssOptions['yttoch3']=='enabled'){array_push($selectors, 'h3');}
//			if ($rssOptions['yttoch4']=='enabled'){array_push($selectors, 'h4');}
//			if ($rssOptions['yttoch5']=='enabled'){array_push($selectors, 'h5');}
//			if ($rssOptions['yttoch6']=='enabled'){array_push($selectors, 'h6');}

			$args = array(
				'css'        => false,
				'to_menu'    => false,
				'title'      => $rssOptions['tocTitleText'],
				'min_found'  => $rssOptions['tocTitlesMin'],
				'min_length' => 100,
				'page_url'   => get_the_permalink(),
				'selectors'  => $selectors,
			);

			$contents = Kama_Contents::init($args)->make_contents($content);

			$contents = str_replace("\n", '', $contents);
			$contents = trim(preg_replace('/\t+/', '', $contents));
			$contents = wpautop($contents);
			$contents = str_replace('<div class="kc__wrap" ><span style="display:block;" class="kc-title kc__title" id="kcmenu">', '<div><h3>', $contents);
			$contents = str_replace('</span></p>', '</h3>', $contents);
			$contents = str_replace(' class="contents"', '', $contents);
			$contents = str_replace(' class="top"', '', $contents);
			$contents = str_replace(' rel="nofollow"', '', $contents);
			$contents = str_replace('<ul>', '<ol>', $contents);
			$contents = str_replace('<ul id="kcmenu">', '<ol>', $contents);
			$contents = str_replace('</ul>', '</ol>', $contents);


			if ($rssOptions['tocPosition'] == 'postBegin') {
				return PHP_EOL . $contents . $content;
			}
			if ($rssOptions['tocPosition'] == 'postEnd') {
				return $content . $contents . PHP_EOL;
			}
			if ($rssOptions['tocPosition'] == 'beforeFirstH') {
				$pattern     = "/<h(.*?)>/i";
				$replacement = $contents . PHP_EOL . '<h$1>';
				$content     = preg_replace($pattern, $replacement, $content, 1);

				return $content;
			}
			if ($rssOptions['tocPosition'] == 'afterFirstH') {
				$pattern     = "/<\/h(.*?)>/i";
				$replacement = '</h$1>' . PHP_EOL . PHP_EOL . $contents;
				$content     = preg_replace($pattern, $replacement, $content, 1);

				return $content;
			}
			return $content;
		}
    }
	if (!function_exists('RFWP_rss_block_feedback')) {
		function RFWP_rss_block_feedback($rssOptions) {
			$content = '';
			if (empty($rssOptions['blockFeedback'])||empty($rssOptions['blockFeedbackButtonOrder'])) {
				return $content;
			}

			$ytfeedbacknetw = explode(";", $rssOptions['blockFeedbackButtonOrder']);
			$ytfeedbacknetw = array_diff($ytfeedbacknetw, array(''));

			if (!empty($ytfeedbacknetw)) {
				foreach ($ytfeedbacknetw as $network) {
					switch ($network) {
						case 'call':
							if ($rssOptions['blockFeedbackButtonContactsCall']) {
								$content .= '<div data-type="call" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsCall']).'"></div>'.PHP_EOL;
							}
							break;
						case 'callback':
							if ($rssOptions['blockFeedbackButtonContactsCallbackEmail']) {
								$content .= '<div data-type="callback" data-send-to="'.esc_url($rssOptions['blockFeedbackButtonContactsCallbackEmail']).'"';
								if ($rssOptions['blockFeedbackButtonContactsCallbackOrganizationName'] && $rssOptions['blockFeedbackButtonContactsCallbackTermsOfUse']) {
									$content .= ' data-agreement-company="'.esc_attr(stripslashes($rssOptions['blockFeedbackButtonContactsCallbackOrganizationName'])).'" data-agreement-link="'.esc_url($rssOptions['blockFeedbackButtonContactsCallbackTermsOfUse']).'"';
								}
							}
							$content .= '></div>'.PHP_EOL;
							break;
						case 'chat':
							$content .= '<div data-type="chat"></div>'.PHP_EOL;
							break;
						case 'mail':
							if ($rssOptions['blockFeedbackButtonContactsMail']) {
								$content .= '<div data-type="mail" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsMail']).'"></div>'.PHP_EOL;
							}
							break;
						case 'vkontakte':
							if ($rssOptions['blockFeedbackButtonContactsVkontakte']) {
								$content .= '<div data-type="vkontakte" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsVkontakte']).'"></div>'.PHP_EOL;
							}
							break;
						case 'odnoklassniki':
							if ($rssOptions['blockFeedbackButtonContactsOdnoklassniki']) {
								$content .= '<div data-type="odnoklassniki" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsOdnoklassniki']).'"></div>'.PHP_EOL;
							}
							break;
						case 'twitter':
							if ($rssOptions['blockFeedbackButtonContactsTwitter']) {
								$content .= '<div data-type="twitter" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsTwitter']).'"></div>'.PHP_EOL;
							}
							break;
						case 'facebook':
							if ($rssOptions['blockFeedbackButtonContactsFacebook']) {
								$content .= '<div data-type="facebook" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsFacebook']).'"></div>'.PHP_EOL;
							}
							break;
						case 'viber':
							if ($rssOptions['blockFeedbackButtonContactsViber']) {
								$content .= '<div data-type="viber" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsViber']).'"></div>'.PHP_EOL;
							}
							break;
						case 'whatsapp':
							if ($rssOptions['blockFeedbackButtonContactsWhatsapp']) {
								$content .= '<div data-type="whatsapp" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsWhatsapp']).'"></div>'.PHP_EOL;
							}
							break;
						case 'telegram':
							if ($rssOptions['blockFeedbackButtonContactsTelegram']) {
								$content .= '<div data-type="telegram" data-url="'.esc_url($rssOptions['blockFeedbackButtonContactsTelegram']).'"></div>'.PHP_EOL;
							}
							break;
					}
				}
				unset($network);
            }

			if (!empty($content))
			{
				$content = PHP_EOL . PHP_EOL . '<div data-block="widget-feedback" data-title="' . esc_attr(stripslashes($rssOptions['blockFeedbackPositionTitle'])) . '" data-stick="' . esc_attr(stripslashes($rssOptions['blockFeedbackPosition'])) . '">' . PHP_EOL . $content . '</div>' . PHP_EOL;
			}

			return $content;
		}
	}
	//функции открытия и закрытия комментариев begin
	if (!function_exists('RFWP_rss_block_comments')) {
	    function RFWP_rss_block_comments( $comment, $args, $depth ) {
		    global $rssOptions;
            $ytcommentsdate = $rssOptions['blockCommentsDate'];
            $ytcommentsdrevo = $rssOptions['blockCommentsTree'];
            $ytcommentsavatar = $rssOptions['blockCommentsAvatars'];
            echo PHP_EOL;
            ?>
            <div data-block="comment"
            data-author="<?php comment_author(); ?>"
            <?php if (!empty($ytcommentsavatar)) { ?>
                data-avatar-url="<?php echo esc_url( get_avatar_url( $comment, 100 ) ); ?>"
            <?php } ?>
            <?php if (!empty($ytcommentsdate)) { ?>
                data-subtitle="<?php echo esc_html(get_comment_date()); ?> в <?php echo esc_html(get_comment_time()); ?>"
            <?php } ?>
            >
            <div data-block="content">
                <?php comment_text(); ?>
            </div>
            <?php if ($args['has_children'] && !empty($ytcommentsdrevo)) {
                ?><?php echo '<div data-block="comments">'; ?><?php 
            }
        }
    }
	if (!function_exists('RFWP_rss_block_comments_end')) {
		function RFWP_rss_block_comments_end($comment, $args, $depth) {
			global $rssOptions;
			$ytcommentsdrevo = $rssOptions['blockCommentsTree'];
			?>
            </div>
			<?php if ($depth==1 && $ytcommentsdrevo=='enabled') {
			    ?><?php echo '</div>'; ?><?php 
			} ?>
		<?php 
		}
    }
    //функции открытия и закрытия комментариев end
	//функция вывода блока поиска begin
	if (!function_exists('RFWP_rss_search_widget')) {
		function RFWP_rss_search_widget($rssOptions) {
			$url = get_bloginfo('url') . '/?s={s}';
			$content = PHP_EOL.'<form action="'.esc_html($url).'" method="GET"><input type="search" name="s" placeholder="'.esc_attr($rssOptions['blockSearchDefaultText']).'" /></form>'.PHP_EOL;

			return $content;
		}
    }
    //функция вывода блока поиска end
	//добавляем колонку "Турбо" в админке на странице списка записей begin
	if (!function_exists('RFWP_rss_add_column_name')) {
		function RFWP_rss_add_column_name($defaults) {
			$purl = plugins_url('', __FILE__);
			$defaults['rb-turbo'] = 'РБ.Турбо';
//			$defaults['rbTurbo'] = '<span class="screen-reader-text">Реалбиг.Турбо</span>';
//            $defaults['rbTurbo'] = '<span class="screen-reader-text">Реалбиг.Турбо</span><img title="Реалбиг.Турбо" style="width: 20px;height: 20px;vertical-align: bottom;" src="'.$purl.'/img/yablocks.png" />';
			return $defaults;
		}
	}
	if (!function_exists('RFWP_rss_css_for_column')) {
		function RFWP_rss_css_for_column() {
			echo '<style>.column-rb-turbo{width: 3.0em;}</style>';
		}
	}
	if (!function_exists('RFWP_rss_add_column_content')) {
		function RFWP_rss_add_column_content($column_name, $post_id) {
			if ($column_name === 'rb-turbo') {
				$rssOptions = RFWP_rssOptionsGet();
				$currentPostType = get_post_type($post_id);
				$content = '<span title="Запись исключена из RSS-ленты (фильтр по типу)" style="vertical-align: middle;color:#72777c;" class="dashicons dashicons-no-alt"></span>';

				if (!empty($rssOptions['typesPost'])&&!empty($currentPostType)) {
					$allowedPostTypes = explode(';', $rssOptions['typesPost']);
					if (in_array($currentPostType, $allowedPostTypes)) {
						$content = '<span title="Запись есть в RSS-ленте" style="vertical-align: middle;color:#0a8f0a;" class="dashicons dashicons-yes"></span>';

//					$ytrssenabled = get_post_meta($post_id, 'ytrssenabled_meta_value', true);
//					$ytremove     = get_post_meta($post_id, 'ytremove_meta_value', true);
//
//					$content = '';
//					if ($ytrssenabled == 'yes') {
//						$content = '<span title="Запись исключена из RSS-ленты (вручную)" style="vertical-align: middle;color:#72777c;" class="dashicons dashicons-no-alt"></span>';
//					}
//					if ($ytremove == 'yes') {
//						$content = '<span title="Турбо-страница на удалении" style="vertical-align: middle;color:#df2424;" class="dashicons dashicons-no-alt"></span>';
//					}
//					if ($ytremove != 'yes' && $ytrssenabled != 'yes') {
//						$content = '<span title="Запись есть в RSS-ленте" style="vertical-align: middle;color:#0a8f0a;" class="dashicons dashicons-yes"></span>';
//					}

						$ytqueryselect = $rssOptions['typesIncludes'];
						$yttaxlist     = $rssOptions['typesTaxExcludes'];
						$ytaddtaxlist  = $rssOptions['typesTaxIncludes'];

						if ($ytqueryselect == 'exclude' && $yttaxlist) {
							$textAr = explode("\n", trim($yttaxlist));
							$textAr = array_filter($textAr, 'trim');
							foreach ($textAr as $line) {
								$tax     = explode(":", $line);
								$taxterm = explode(",", $tax[1]);
								$taxterm = array_map('intval', $taxterm);
								if (has_term($taxterm, $tax[0])) {
									$content = '<span title="Запись исключена из RSS-ленты (фильтр по таксономии)" style="vertical-align: middle;color:#72777c;" class="dashicons dashicons-no-alt"></span>';
									break;
								}
							}
						}
						if (!$ytaddtaxlist) {
							$ytaddtaxlist = 'category:10000000';
						}
						if ($ytqueryselect == 'include' && $ytaddtaxlist) {
							$textAr = explode("\n", trim($ytaddtaxlist));
							$textAr = array_filter($textAr, 'trim');
							foreach ($textAr as $line) {
								$tax     = explode(":", $line);
								$taxterm = explode(",", $tax[1]);
								$taxterm = array_map('intval', $taxterm);
								if (has_term($taxterm, $tax[0])) {
									$content = '<span title="Запись есть в RSS-ленте" style="vertical-align: middle;color:#0a8f0a;" class="dashicons dashicons-yes"></span>';
									break;
								} else {
									$content = '<span title="Запись исключена из RSS-ленты (фильтр по таксономии)" style="vertical-align: middle;color:#72777c;" class="dashicons dashicons-no-alt"></span>';
								}
							}
						}

						if (get_post_status($post_id) != 'publish') {
							$content = '<span title="Записи нет в RSS-ленте (запись не опубликована)" style="vertical-align: middle;color:#72777c;" class="dashicons dashicons-no-alt"></span>';
						}
					}
				}
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $content;
			}
		}
	}
	if (!function_exists('RFWP_rss_add_columns')) {
		function RFWP_rss_add_columns() {
			global $rssOptions;
			if (empty($rssOptions)) {
				$rssOptions = RFWP_rssOptionsGet();
			}

			if (empty($rssOptions['typesInAdminCol'])) {
				return;
			}

			$yttype = explode(";", $rssOptions['typesPost']);
			$yttype = array_diff( $yttype, array('') );

			foreach ( $yttype as $post_type ) {
				if ( 'page' === $post_type ) continue;
				add_filter( "manage_{$post_type}_posts_columns", "RFWP_rss_add_column_name", 5 );
				add_action( "manage_{$post_type}_posts_custom_column", "RFWP_rss_add_column_content", 5, 2 );
			}

			if ( in_array('page', $yttype) ) {
				add_filter( 'manage_pages_columns', 'RFWP_rss_add_column_name', 5 );
				add_action( 'manage_pages_custom_column', 'RFWP_rss_add_column_content', 5, 2 );
			}
			add_action('admin_head', 'RFWP_rss_css_for_column');
		}
	}
	add_action('wp_loaded', 'RFWP_rss_add_columns');
	//добавляем колонку "Турбо" в админке на странице списка записей end
	if (!function_exists('RFWP_rss_taxonomy_get')) {
		function RFWP_rss_taxonomy_get($rssOptions) {
			$typesIncludes = $rssOptions['typesIncludes'];
			$typesTaxExcludes = $rssOptions['typesTaxExcludes'];
			$typesTaxIncludes = $rssOptions['typesTaxIncludes'];
			$tax_query = [];
			$allowedTypesArray = ['exclude','include'];
			$typesTaxField = '';
			$operatorField = '';
			if (!empty($typesIncludes)&&in_array($typesIncludes, $allowedTypesArray)) {
				if ($typesIncludes=='exclude'&&!empty($typesTaxExcludes)) {
					$typesTaxField = $typesTaxExcludes;
					$operatorField = 'NOT IN';
				} elseif ($typesIncludes=='include'&&!empty($typesTaxIncludes)) {
					$typesTaxField = $typesTaxIncludes;
					$operatorField = 'IN';
				}
				if (!empty($typesTaxField)&&!empty($operatorField)) {
					$textAr = explode("\n", trim($typesTaxField));
					$textAr = array_filter($textAr, 'trim');
					$tax_query = array( 'relation' => 'AND' );
					foreach ($textAr as $line) {
						$tax = explode(":", $line);
						$taxterm = explode(",", $tax[1]);
						$tax_query[] = array(
							'taxonomy' => $tax[0],
							'field'    => 'id',
							'terms'    => $taxterm,
							'operator' => $operatorField,
						);
					}
				}
			}
			return $tax_query;
		}
    }
	//функция удаления тегов вместе с их контентом begin
	if (!function_exists('RFWP_rss_strip_tags_with_content')) {
		function RFWP_rss_strip_tags_with_content($text, $tags = '', $invert = FALSE) {
			// удаляем лишние символы, добавляем тегам символы <> begin
			$tags = preg_replace('/[^A-Za-z0-9,]/', '', $tags);
			$a    = explode(";", $tags);
			$a    = array_diff($a, array(''));
			array_walk($a, function (&$value, $key) {
				$value = '<' . $value . '>';
			});
			$tags = implode(";", $a);
			// удаляем лишние символы, добавляем тегам символы <> end

			preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags_array);
			$tags_array = array_unique($tags_array[1]);

			$regex = '';

			if (count($tags_array) > 0) {
				if ( ! $invert) {
					$regex = '@<(?!(?:'.implode('|', $tags_array) . ')\b)(\w+)\b[^>]*?(>((?!<\1\b).)*?<\/\1|\/)>@si';
					$text  = preg_replace($regex, '', $text);
				} else {
					$regex = '@<(' . implode('|', $tags_array) . ')\b[^>]*?(>((?!<\1\b).)*?<\/\1|\/)>@si';
					$text  = preg_replace($regex, '', $text);
				}
			} elseif (!$invert) {
				$regex = '@<(\w+)\b[^>]*?(>((?!<\1\b).)*?<\/\1|\/)>@si';
				$text  = preg_replace($regex, '', $text);
			}

			if ($regex && preg_match($regex, $text)) {
				$text = RFWP_rss_strip_tags_with_content($text, $tags, $invert);
			}

			return $text;
		}
	}
    //функция удаления тегов вместе с их контентом end
    //функция удаления тегов без их контента begin
	if (!function_exists('RFWP_rss_strip_tags_without_content')) {
		function RFWP_rss_strip_tags_without_content($text, $tags = '') {
			// удаляем лишние символы, добавляем тегам символы <> begin
			$tags = preg_replace('/[^A-Za-z0-9,]/', '', $tags);
			$a = explode(";", $tags );
			$a = array_diff($a, array(''));
			array_walk($a, function(&$value, $key) { $value = '<'. $value . '>'; } );
			$tags = implode(";", $a );
			// удаляем лишние символы, добавляем тегам символы <> end

			preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
			$tags = array_unique($tags[1]);

			if(is_array($tags) AND count($tags) > 0) {
				foreach($tags as $tag)  {
					$text = preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/", '', $text);
				}
			}
			return $text;
		}
    }
    //функция удаления тегов без их контента end
	//функция удаления указанных шорткодов begin
	if (!function_exists('RFWP_rss_strip_shortcodes')) {
		function RFWP_rss_strip_shortcodes($content, $rssOptions) {
			//выполняем блоки гутенберга
			global $wp_version;
			if (version_compare($wp_version, '5.0', '>=')) {
				$content = do_blocks($content);
			}

			if (empty($rssOptions['filterSc'])||empty($rssOptions['filterScField'])) {
				return $content;
			}

			global $shortcode_tags;
			$stack = $shortcode_tags;

			$code = explode(";", $rssOptions['filterScField']);
			$code = array_diff($code, array(''));

			$how_many = count($code);
			for ($i = 0; $i < $how_many; $i ++) {
				$arr[$code[$i]] = 1;
			}

			$shortcode_tags = $arr;
			$content        = strip_shortcodes($content);
			$shortcode_tags = $stack;

			return $content;
		}
    }
	//функция удаления указанных шорткодов end
    //функция формирования контента по шаблону begin
	if (!function_exists('RFWP_rss_build_template')) {
		function RFWP_rss_build_template($post_content, $rssOptions, $postId) {
			$post_type = get_post_type($postId);

			if (empty($rssOptions['template-'.$post_type])) {
				$rssOptions['template-'.$post_type] = '%%post_content%%';
			}

			$content = html_entity_decode(stripcslashes($rssOptions['template-' . $post_type]), ENT_QUOTES);


			//проверка на индивидуальный шаблон записи (если включен и существует)
			$template_meta = get_post_meta($postId, 'template_meta', true);
			if ($template_meta == 'yes') {
				$custom_template = get_post_meta($postId, 'custom_template', true);
				$custom_template = html_entity_decode(stripcslashes($custom_template), ENT_QUOTES);
				if (!$custom_template) {
					$custom_template = $content;
				}
				$content = $custom_template;
			}

			//сначала обработаем шаблон произвольным фильтром
			$content = apply_filters('yturbo_the_template', $content);

			//заменяем переменные на произвольные поля
			if (preg_match_all("/%%(.*?)%%/i", $content, $res)) {
				foreach ($res[0] as $r) {
					if ($r != '%%post_content%%') {
						$temp    = str_replace('%%', '', $r);
						$content = str_replace($r, get_post_meta($postId, $temp, true), $content);
					}
				}
			}

			//обрабатываем шаблон фильтрами для RSS
			$content = do_shortcode($content);
			$content = str_replace(']]>', ']]&gt;', $content);
			$content = apply_filters('wp_staticize_emoji', $content);
			$content = apply_filters('_oembed_filter_feed_content', $content);

			//заменяем в шаблоне переменную %%post_content%% на контент записи
			$content = str_replace('<p>%%post_content%%</p>', '%%post_content%%', $content);
			$content = str_replace('%%post_content%%', $post_content, $content);

			return $content;
		}
	}
    //функция формирования контента по шаблону end
	//функция отслеживания урлов удаляемых записей begin
	if (!function_exists('RFWP_rss_trash_tracking')) {
		function RFWP_rss_trash_tracking($post_id) {
			$rssOptions = RFWP_rssOptionsGet();

			if (empty($rssOptions['selectiveOff'])) {return;}
			if (empty($rssOptions['selectiveOffTracking'])) {return;}

			$yttype = explode(";", $rssOptions['typesPost']);
			$yttype = array_diff($yttype, array(''));

			if (!in_array(get_post_type($post_id), $yttype)) {return;}

			$rfwp_selectiveOffFieldGet = RFWP_rssSelectiveOffFieldGet();
			$rfwp_selectiveOffField = RFWP_rssSelectiveOffFieldToArray($rfwp_selectiveOffFieldGet);

			$delpermalink = PHP_EOL . esc_url(apply_filters('the_permalink_rss', get_permalink($post_id)));
			$delpermalink = trim($delpermalink);
			foreach ($rfwp_selectiveOffField as $k => $item) {
				if (in_array($delpermalink, $rfwp_selectiveOffField[$k])) {
					$neededKey = array_search($delpermalink, $rfwp_selectiveOffField[$k]);
					if ($neededKey!==false&&$k=='restore') {
						unset($rfwp_selectiveOffField[$k][$neededKey]);
					}
				} else {
					if ($k=='delete') {
						array_push($rfwp_selectiveOffField[$k], $delpermalink);
					}
				}
				RFWP_rssSelectiveOffFieldOptionSave($rfwp_selectiveOffField[$k], $k);
			}
			unset($k, $item);
		}
	}
	add_action('wp_trash_post', 'RFWP_rss_trash_tracking');
	//функция отслеживания урлов удаляемых записей end
	if (!function_exists('RFWP_rssSelectiveOffFieldGet')) {
		function RFWP_rssSelectiveOffFieldGet() {
			$result = [];
			$result['delete'] = get_option('rfwp_selectiveOffField');
			if ($result['delete']===false) {
				$result['delete'] = '';
            }
			$result['restore'] = get_option('rfwp_selectiveOffFieldRestore');
			if ($result['restore']===false) {
				$result['restore'] = '';
            }

			return $result;
        }
    }
	if (!function_exists('RFWP_rssSelectiveOffFieldToArray')) {
	    function RFWP_rssSelectiveOffFieldToArray($selectiveOffField) {
		    $result = [];
		    $result['delete'] = [];
		    $result['restore'] = [];
		    foreach ($selectiveOffField as $k => $item) {
			    if (!empty($selectiveOffField[$k])) {
				    if (is_string($selectiveOffField[$k])) {
//					    $selectiveOffField[$k] = explode("\n", str_replace(array("\r\n", "\r"), "\n", $selectiveOffField[$k]));
					    $selectiveOffField[$k] = explode(";", $selectiveOffField[$k]);
				    }
				    $result[$k] = $selectiveOffField[$k];
			    }
		    }
		    unset($k, $item);

		    return $result;
        }
    }
	if (!function_exists('RFWP_rssSelectiveOffFieldToString')) {
	    function RFWP_rssSelectiveOffFieldToString($selectiveOffField) {
		    $result = '';
		    if (!empty($selectiveOffField)&&is_array($selectiveOffField)) {
//			    $result = implode('\n', $selectiveOffField);
			    $result = implode(';', $selectiveOffField);
            }

		    return $result;
        }
    }
	if (!function_exists('RFWP_rssSelectiveOffFieldOptionSave')) {
	    function RFWP_rssSelectiveOffFieldOptionSave($value, $type) {
		    $value = RFWP_rssSelectiveOffFieldToString($value);
		    if ($type=='delete') {
			    update_option('rfwp_selectiveOffField', $value);
		    } elseif ($type=='restore') {
			    update_option('rfwp_selectiveOffFieldRestore', $value);
		    }
        }
    }
	//функция отслеживания урлов восстанавливаемых записей begin
	if (!function_exists('RFWP_rss_untrash_tracking')) {
		function RFWP_rss_untrash_tracking($post_id) {
			$rssOptions = RFWP_rssOptionsGet();

			if (empty($rssOptions['selectiveOff'])) {return;}
			if (empty($rssOptions['selectiveOffTracking'])) {return;}

			$yttype = explode(";", $rssOptions['typesPost']);
			$yttype = array_diff($yttype, array(''));

			if (!in_array(get_post_type($post_id), $yttype)) {return;}

			$rfwp_selectiveOffFieldGet = RFWP_rssSelectiveOffFieldGet();
			$rfwp_selectiveOffField = RFWP_rssSelectiveOffFieldToArray($rfwp_selectiveOffFieldGet);

			$restorepermalink = esc_url(apply_filters('the_permalink_rss', get_permalink($post_id)));
			$restorepermalink = trim($restorepermalink);
			foreach ($rfwp_selectiveOffField as $k => $item) {
				if (in_array($restorepermalink, $rfwp_selectiveOffField[$k])) {
					$neededKey = array_search($restorepermalink, $rfwp_selectiveOffField[$k]);
					if ($neededKey!==false&&$k=='delete') {
                        unset($rfwp_selectiveOffField[$k][$neededKey]);
					}
				} else {
					if ($k=='restore') {
						array_push($rfwp_selectiveOffField[$k], $restorepermalink);
					}
                }
				RFWP_rssSelectiveOffFieldOptionSave($rfwp_selectiveOffField[$k], $k);
            }
			unset($k, $item);
		}
	}
	add_action('untrashed_post', 'RFWP_rss_untrash_tracking');
	//функция отслеживания урлов восстанавливаемых записей end
	//функция вывода мусорной ленты begin
	if (!function_exists('RFWP_rss_lenta_trash')) {
		function RFWP_rss_lenta_trash($rssOptions) {
			header('Content-Type: ' . feed_content_type('rss2') . '; charset=' . esc_html(get_option('blog_charset')), true);
			echo '<?xml version="1.0" encoding="'.esc_html(get_option('blog_charset')).'"?'.'>'.PHP_EOL;
			?>
			<rss
				xmlns:yandex="http://news.yandex.ru"
				xmlns:media="http://search.yahoo.com/mrss/"
				xmlns:turbo="http://turbo.yandex.ru"
				version="2.0">
				<channel>
					<turbo:cms_plugin>C125AEEC6018B4A0EF9BF40E6615DD17</turbo:cms_plugin>
					<title><?php echo esc_html($rssOptions['title']); ?></title>
					<link><?php echo esc_url($rssOptions['url']); ?></link>
					<description><?php echo esc_html($rssOptions['description']); ?></description>
					<language><?php echo esc_html($rssOptions['lang']); ?></language>
					<generator>RSS for Yandex Turbo v<?php echo esc_html($rssOptions['version']); ?> (https://wordpress.org/plugins/rss-for-yandex-turbo/)</generator>
					<?php
					$rfwp_selectiveOffFieldGet = get_option('rfwp_selectiveOffField');
					$textAr = [];
					$selectiveOffField = [];
					if (!empty($rssOptions['selectiveOffField'])) {
						$selectiveOffField = $rssOptions['selectiveOffField'];
						if (is_string($selectiveOffField)) {
							$selectiveOffField = explode("\n", str_replace(array("\r\n", "\r"), "\n", $rssOptions['selectiveOffField']));
						}
						$textAr = $selectiveOffField;
					}
                    if (!empty($rfwp_selectiveOffFieldGet)) {
                        if (is_string($rfwp_selectiveOffFieldGet)) {
	                        $rfwp_selectiveOffFieldGet = json_decode($rfwp_selectiveOffFieldGet);
                        }
                    }

                    if (!empty($rfwp_selectiveOffFieldGet)) {
	                    foreach ($rfwp_selectiveOffFieldGet AS $k1 => $item1) {
		                    if (!in_array($item1, $textAr)) {
			                    array_push($textAr, $item1);
		                    }
	                    }
                    }
					unset($k1, $item1);

                    if (!empty($textAr)) {
						$i = 0;
						foreach ($textAr as $line) {
							echo ($i > 0 ? "" : "    ") .  '<item turbo="false"><link>' . esc_url(stripcslashes($line)) . '</link></item>' . PHP_EOL;
							$i++;
						}
					} else {
						//чтобы яндекс не ругался на пустую ленту, если на удалении нет записей
						echo '<item turbo="false"><link>' . esc_url(get_bloginfo_rss('url')) . '/musor-page/</link></item>' . PHP_EOL;
					}
					?>
				</channel>
			</rss>
		<?php }
	}
	//функция вывода мусорной ленты end
	if (!function_exists('RFWP_rss_turbo_ads_insert')) {
	    function RFWP_rss_turbo_ads_insert($content) {
	        global $rb_turboAds;
	        if (!empty($rb_turboAds)) {
		        foreach ($rb_turboAds AS $k => $item) {
			        $editedContent = $content;
			        $currentFigure = '<figure data-turbo-ad-id="rb_turbo_ad_'.$k.'"></figure>';
		            switch ($item['settingType']) {
//		                case 'single','begin','middle','end':
		                case 'begin':
			                $editedContent = $currentFigure.$editedContent;
		                    break;
                        case 'end':
	                        $editedContent = $editedContent.$currentFigure;
	                        break;
                        case 'middle':
                            $contentLength = strlen($editedContent);
                            $contentHalfLength = floor($contentLength/2);
                            if ($contentHalfLength > 1) {
	                            $firstHalf = mb_substr($editedContent, 0, ($contentHalfLength-1));
	                            $secondHalf = mb_substr($editedContent, $contentHalfLength);
	                            $secondHalf = preg_replace('~(\<\/[^>]+\>)~', '$1'.$currentFigure, $secondHalf, 1, $replCou);
	                            if ($replCou > 0) {
		                            $editedContent = $firstHalf.$secondHalf;
                                }
	                            unset($replCou);
                            }
                            break;
                        case 'single':
	                        if ($item['element']!='img') {
		                        if ($item['elementPosition'] < 1) {
			                        $editedContent = preg_replace('~(\<'.$item['element'].'[^>]*\>)~', '<rb_turbo_ad_placeholder>$1', $editedContent, -1, $replCou);
		                        } else {
			                        $editedContent = preg_replace('~(\<\/'.$item['element'].'\>)~', '$1<rb_turbo_ad_placeholder>', $editedContent, -1, $replCou);
		                        }
	                        } else {
		                        if ($item['elementPosition']<1) {
			                        $editedContent = preg_replace('~(\<'.$item['element'].'[^>]*\>)~', '<rb_turbo_ad_placeholder>$1', $editedContent, -1, $replCou);
		                        } else {
			                        $editedContent = preg_replace('~(\<'.$item['element'].'[^>]*\>)~', '$1<rb_turbo_ad_placeholder>', $editedContent, -1, $replCou);
		                        }
	                        }
	                        if ($replCou>0) {
	                            if ($item['elementPlace'] > 0) {
	                                $elementPlace = $item['elementPlace'];
                                } else {
		                            $elementPlace = (int)$replCou+(int)$item['elementPlace']+1;
                                }
		                        if ($elementPlace>0 && $elementPlace<=$replCou) {
			                        $editedContent = preg_replace('~\<rb\_turbo\_ad\_placeholder\>~', '', $editedContent, ($elementPlace-1));
			                        $editedContent = preg_replace('~\<rb\_turbo\_ad\_placeholder\>~', $currentFigure, $editedContent, 1);
		                        }
		                        $editedContent = preg_replace('~\<rb\_turbo\_ad\_placeholder\>~', '', $editedContent);
                            }
	                        unset($replCou);
	                        break;
                    }
                    if (!empty($editedContent)) {
	                    $content = $editedContent;
                    } else {
	                    $editedContent = $content;
                    }
                }
		        unset($k,$item);
            }

	        return $content;
        }
    }
	if (!function_exists('RFWP_rss_turbo_ads_construct')) {
		function RFWP_rss_turbo_ads_construct() {
		    global $rb_turboAds;
		    $ads = '';
			if (!empty($rb_turboAds)) {
				foreach ($rb_turboAds AS $k => $item) {
					if ($item['adNetwork']=='rsya') {
						$ads .= '<turbo:adNetwork type="Yandex" id="'.$item['adNetworkYandex'].'" turbo-ad-id="rb_turbo_ad_'.$k.'"></turbo:adNetwork>'.PHP_EOL;
					} elseif ($item['adNetwork']=='adfox') {
						$ads .= '<turbo:adNetwork type="AdFox" turbo-ad-id="rb_turbo_ad_'.$k.'"><![CDATA['.htmlspecialchars_decode($item['adNetworkAdfox'], ENT_QUOTES).']]></turbo:adNetwork>'.PHP_EOL;
                    }
                }
				unset($k,$item);
            }

		    return $ads;
        }
    }
	if (!function_exists('RFWP_filter_permalink_rss')) {
		function RFWP_filter_permalink_rss($url) {
			$turboOptions = RFWP_rssOptionsGet();

			if (!is_feed($turboOptions['name'])) {
				return $url;
            }
			if (!empty($turboOptions['onTurbo'])) {
				return $url;
            }
			if ($turboOptions['onOffProtocol'] == 'default') {
				return $url;
            }
			if ($turboOptions['onOffProtocol'] == 'http') {
				$url = str_replace('https://', 'http://', $url);
			}
			if ($turboOptions['onOffProtocol'] == 'https') {
				$url = str_replace('http://', 'https://', $url);
			}

			return $url;
		}
    }
//	add_filter( 'the_permalink_rss', 'RFWP_filter_permalink_rss' );
	if (!function_exists('RFWP_rssCreate')) {
		function RFWP_rssCreate() {

			$rssOptions = RFWP_rssOptionsGet();
			$GLOBALS['$rssOptions'] = $rssOptions;
			$rssDivided = [];
			$rssDivided[0] = [];
			$rssDivideOptions = $GLOBALS['rb_rssDivideOptions'];
			$paged = 0;
			$postTypes = ['post','page'];

			$messageFLog = 'point 1;';
            RFWP_Logs::saveLogs(RFWP_Logs::RSS_LOG, $messageFLog);

            //@codingStandardsIgnoreStart
			if (!empty($_GET)&&!empty($_GET['paged'])) {
				$paged = (intval($_GET['paged'])-1);
            }

			$messageFLog = 'values: ';
			if (isset($_GET)) {
				$messageFLog .= 'get_string: '.implode(';', $_GET).';';
				$messageFLog .= 'get_count: '.count($_GET).';';
            }
			if (isset($_POST)) {
				$messageFLog .= 'post_string: '.implode(';', $_POST).';';
				$messageFLog .= 'post_count: '.count($_POST).';';
            }

            RFWP_Logs::saveLogs(RFWP_Logs::RSS_LOG, $messageFLog);

			if (isset($_GET['rb_rss_trash']) && $_GET['rb_rss_trash']=='1') {
//				if (!empty($rssOptions['selectiveOff'])) {
					RFWP_rss_lenta_trash($rssOptions);
//				}
				$messageFLog = 'point 2;';
                RFWP_Logs::saveLogs(RFWP_Logs::RSS_LOG, $messageFLog);

				exit;
			}
            //@codingStandardsIgnoreEnd

			if (!empty($rssDivideOptions['posts'][$paged])) {
			    $localPosts = $rssDivideOptions['posts'][$paged];
			    $localPostsId = [];
			    foreach ($localPosts AS $k => $item) {
				    array_push($localPostsId, $item->ID);
                }
			    unset($k,$item);
				$rssPosts = get_posts([
					'numberposts' => -1,
					'post_type' => $postTypes,
//                    'post__in' => $rssDivideOptions['posts'][$paged]
                    'post__in' => $localPostsId
				]);
				if (!empty($rssPosts)) {
					$rssPosts = RFWP_rssPostsCleaning($rssPosts, $rssOptions);
					$ads = RFWP_rss_turbo_ads_construct();
					$rssDivided[0] = $rssPosts;
				}
            }

			$rssOptions['contentType'] = feed_content_type('rss2');
			$rssOptions['charset'] = get_option('blog_charset');

			$messageFLog = 'point 3;';
            RFWP_Logs::saveLogs(RFWP_Logs::RSS_LOG, $messageFLog);

			if (!empty($rssDivided)) {
				foreach ($rssDivided AS $k1 => $item1) {
					$messageFLog = 'point 4;';
                    RFWP_Logs::saveLogs(RFWP_Logs::RSS_LOG, $messageFLog);

					header('Content-Type: '.$rssOptions['contentType'].'; charset='.esc_html($rssOptions['charset']), true);
					echo '<?xml version="1.0" encoding="'.esc_html($rssOptions['charset']).'"?'.'>'.PHP_EOL;
					?>
					<rss
						xmlns:yandex="http://news.yandex.ru"
						xmlns:media="http://search.yahoo.com/mrss/"
						xmlns:turbo="http://turbo.yandex.ru"
						version="2.0">
						<channel>
							<!-- Информация о сайте-источнике -->
                            <testTime><?php echo esc_html(current_time('mysql')); ?></testTime>
							<title><?php echo esc_attr(stripslashes($rssOptions['title'])) ?></title>
							<link><?php echo esc_url($rssOptions['url']) ?></link>
							<description><?php echo esc_attr(stripslashes($rssOptions['description'])) ?></description>
							<?php if (!empty($rssOptions['couYandexMetrics'])) { ?><turbo:analytics id="<?php echo esc_attr(stripslashes($rssOptions['couYandexMetrics'])); ?>" type="Yandex"></turbo:analytics><?php echo PHP_EOL; ?><?php } ?>
							<?php if (!empty($rssOptions['couLiveInternet'])) { ?><turbo:analytics type="LiveInternet"></turbo:analytics><?php echo PHP_EOL; ?><?php } ?>
							<?php if (!empty($rssOptions['couGoogleAnalytics'])) { ?><turbo:analytics id="<?php echo esc_attr(stripslashes($rssOptions['couGoogleAnalytics'])); ?>" type="Google"></turbo:analytics><?php echo PHP_EOL; ?><?php } ?>
                            <language><?php echo esc_attr(stripslashes($rssOptions['lang'])) ?></language>
							<?php if (!empty($rssOptions['analytics'])): ?>
								<turbo:analytics></turbo:analytics>
							<?php endif; ?>
                            <? // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php if (!empty($ads)) {echo $ads;} ?>
							<?php if (!empty($rssOptions['adNetwork'])): ?>
								<turbo:adNetwork></turbo:adNetwork>
							<?php endif; ?>
                            <?php if (!empty($item1)): ?>
                                <?php $imageSizes = RFWP_getThumbnailsSizes(); ?>
							    <?php foreach ($item1 AS $k => $item): ?>
                                    <item turbo="<?php echo esc_attr($rssOptions['onTurbo']) ?>">
                                        <!-- Информация о странице -->
                                        <title><?php echo esc_html(RFWP_rss_cut_by_words(237, $item->post_title)); ?></title>
                                        <link><?php echo esc_url($item->guid) ?></link>
                                        <turbo:source><?php echo esc_url($item->guid) ?></turbo:source>
                                        <turbo:topic><?php echo esc_html($item->post_title) ?></turbo:topic>
                                        <?php if (!empty($rssOptions['PostHtml'])): ?>
                                            <turbo:extendedHtml>true</turbo:extendedHtml>
                                        <?php endif; ?>
                                        <?php if (!empty($rssOptions['PostDate'])): ?>
                                            <?php if ($rssOptions['PostDateType'] == 'create'&&!empty($item->post_date_gmt)) { ?>
                                                <pubDate><?php echo esc_html($item->post_date_gmt) ?> +0300</pubDate>
                                            <?php } elseif ($rssOptions['PostDateType'] == 'edit'&&!empty($item->post_modified_gmt)) { ?>
                                                <pubDate><?php echo esc_html($item->post_modified_gmt) ?> +0300</pubDate>
                                            <?php } ?>
                                        <?php endif; ?>
                                        <?php if ($rssOptions['PostAuthor'] != 'disable') { ?>
                                            <?php if (!empty($rssOptions['PostAuthorDirect'])&&$rssOptions['PostAuthor'] != 'enable') {
                                                echo '<author>'.esc_html($rssOptions['PostAuthorDirect']).'</author>'.PHP_EOL;
                                            } else {
                                                echo '<author>'.esc_html($item->post_author_name).'</author>'.PHP_EOL;
                                            }
                                        } ?>
                                        <?php /* <yandex:related></yandex:related> /**/ ?>
                                        <turbo:content>
                                            <![CDATA[
                                            <header>
                                                <?php if (!empty($rssOptions['Thumbnails'])&&isset($rssOptions['ThumbnailsSize'])&&has_post_thumbnail($item->ID)) {
                                                    $size = !empty($imageSizes[$rssOptions['ThumbnailsSize']]) ? $imageSizes[$rssOptions['ThumbnailsSize']] : '';
                                                    echo '<figure><img src="'. esc_url(strtok(get_the_post_thumbnail_url($item->ID, $size), '?')).'" /></figure>'.PHP_EOL;
                                                } ?>
                                                <?php if ($rssOptions['PostTitle']) {
                                                    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                                                    $localTitle = '';
                                                    if ($rssOptions['SeoPlugin'] == 'yoast_seo') {
                                                        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
                                                            $localTitle = get_post_meta($item->ID, '_yoast_wpseo_title', true);
                                                            $localTitle = str_replace(array(
                                                                '%%title%%',
                                                                '%%sitename%%',
                                                                '%%sep%%',
                                                                '%%page%%'
                                                            ), array($item->post_title, $rssOptions['title'], '-', ''), $localTitle);
                                                            $localTitle = str_replace('  ', ' ', $localTitle);
                                                            if (!$localTitle) {
                                                                $wpseo_titles = get_option('wpseo_titles');
                                                                $sep_options  = WPSEO_Option_Titles::get_instance()->get_separator_options();
                                                                if (isset($wpseo_titles['separator']) && isset($sep_options[$wpseo_titles['separator']])) {
                                                                    $sep = $sep_options[$wpseo_titles['separator']];
                                                                } else {
                                                                    $sep = '-';
                                                                }
                                                                $localTitle = str_replace(array(
                                                                    '%%title%%',
                                                                    '%%sitename%%',
                                                                    '%%sep%%',
                                                                    '%%page%%'
                                                                ), array(
                                                                    $item->post_title,
                                                                    $rssOptions['title'],
                                                                    $sep,
                                                                    ''
                                                                ), $wpseo_titles['title-' . get_post_type($item->ID)]);
                                                            }
                                                        } else {
                                                            $localTitle = $item->post_title;
                                                        }
                                                        if (!$localTitle) {
                                                            $localTitle = $item->post_title;
                                                        }
                                                        $localTitle = apply_filters('convert_chars', $localTitle);
                                                        $localTitle = apply_filters('ent2ncr', $localTitle, 8);
                                                        $localTitle = RFWP_rss_remove_emoji($localTitle);
                                                        $localTitle = RFWP_rss_cut_by_words(237, $localTitle);
                                                        echo "<h1>" . esc_html($localTitle) . "</h1>" . PHP_EOL;
                                                    }
                                                    if ($rssOptions['SeoPlugin'] == 'all_in_one_seo_pack') {
                                                        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
                                                            $localTitle = get_post_meta($item->ID, '_aioseop_title', true);
                                                            $localTitle = str_replace(array(
                                                                '%page_title%',
                                                                '%blog_title%',
                                                                '%post_title%'
                                                            ), array($item->post_title, $rssOptions['title'], $item->post_title), $localTitle);
                                                            $localTitle = str_replace('  ', ' ', $localTitle);
                                                            if ( ! $localTitle) {
                                                                global $aioseop_options;
                                                                $localTitle = str_replace(array(
                                                                    '%page_title%',
                                                                    '%blog_title%',
                                                                    '%post_title%'
                                                                ), array(
                                                                    $item->post_title,
                                                                    $rssOptions['title'],
                                                                    $item->post_title
                                                                ), $aioseop_options['aiosp_' . get_post_type() . '_title_format']);
                                                            }
                                                        } else {
                                                            $localTitle = $item->post_title;
                                                        }
                                                        if (!$localTitle) {
                                                            $localTitle = $item->post_title;
                                                        }
                                                        $localTitle = apply_filters('convert_chars', $localTitle);
                                                        $localTitle = apply_filters('ent2ncr', $localTitle, 8);
                                                        $localTitle = RFWP_rss_remove_emoji($localTitle);
                                                        $localTitle = RFWP_rss_cut_by_words(237, esc_html($localTitle));
                                                        echo "<h1>" . esc_html($localTitle) . "</h1>" . PHP_EOL;
                                                    }
                                                } else { ?>
                                                    <h1><?php echo esc_html(RFWP_rss_cut_by_words(237, $item->post_title)); ?></h1>
                                                <?php } ?>
                                                <?php if ($rssOptions['menu']!='not_use') {
                                                    echo '<menu>'.PHP_EOL;
                                                    $menu = wp_get_nav_menu_object($rssOptions['menu']);
                                                    $menu_items = wp_get_nav_menu_items($menu->term_id);

                                                    foreach ((array) $menu_items as $key => $menu_item) {
                                                        $title = $menu_item->title;
                                                        $url = $menu_item->url;
                                                        echo '<a href="'.esc_url($url).'">'.esc_html($title).'</a>'.PHP_EOL;
                                                    }
                                                    unset($key,$menu_item);

                                                    echo '</menu>'.PHP_EOL;
                                                } ?>
                                            </header>
                                            <?php if (!empty($rssOptions['blockRating'])) {
                                                $temprating = wp_rand($rssOptions['blockRatingFrom']*100, $rssOptions['blockRatingTo']*100) / 100;
                                                echo '<div itemscope itemtype="http://schema.org/Rating">
                                                        <meta itemprop="ratingValue" content="'.esc_attr($temprating).'">
                                                        <meta itemprop="bestRating" content="' . esc_attr(max($rssOptions['blockRatingTo'], 5)) . '">
                                                    </div>';
                                            } ?>
                                            <?php if (!empty($rssOptions['blockSearch'])&&$rssOptions['blockSearchPosition'] == 'postBegin') {
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo RFWP_rss_search_widget($rssOptions);
                                            } ?>
                                            <?php if (!empty($rssOptions['blockFeedback']) && $rssOptions['blockFeedbackPosition'] == 'false' && $rssOptions['blockFeedbackPositionPlace'] == 'begin') {
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo RFWP_rss_block_feedback($rssOptions);
                                            } ?>
	                                        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo htmlspecialchars_decode($item->post_content) ?>
                                            <?php if (!empty($rssOptions['blockShare'])) {
                                                echo PHP_EOL.'<div data-block="share" data-network="'.esc_attr($rssOptions['blockShareOrder']).'"></div>';
    //											if ($ytad4 == 'enabled' && $ytad4meta != 'disabled') { echo PHP_EOL.'<figure data-turbo-ad-id="fourth_ad_place"></figure>'.PHP_EOL; }
                                                do_action( 'yturbo_after_share' );
                                            } ?>
                                            <?php if (!empty($rssOptions['blockFeedback']) && $rssOptions['blockFeedbackPosition'] == 'false' && $rssOptions['blockFeedbackPositionPlace'] == 'end') {
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo RFWP_rss_block_feedback($rssOptions);
                                            } ?>
                                            <?php if (!empty($rssOptions['blockFeedback']) && $rssOptions['blockFeedbackPosition'] != 'false') {
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo RFWP_rss_block_feedback($rssOptions);
                                            } ?>
                                            <?php if (!empty($rssOptions['blockSearch'])&&$rssOptions['blockSearchPosition'] == 'postEnd') {
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo RFWP_rss_search_widget($rssOptions);
                                            } ?>
                                            <?php if (!empty($rssOptions['blockComments'])) {
                                                if ($rssOptions['blockCommentsTree']=='enabled') {
                                                    $ytcommentsdrevo = 2;
                                                } else {
                                                    $ytcommentsdrevo = 1;
                                                }
                                                if ($rssOptions['blockCommentsSort']=='new_in_begin'){
                                                    $reverse_top_level=false;
                                                    $reverse_children=false;
                                                } else {
                                                    $reverse_top_level=true;
                                                    $reverse_children=true;
                                                }
                                                $comments = get_comments(array(
                                                    'post_id' => $item->ID,
                                                    'status' => 'approve',
                                                ));
                                                if (!empty($comments)) {
                                                    echo PHP_EOL.'<div data-block="comments" data-url="'.esc_url(get_permalink($item->ID)).'#respond">';
                                                }
                                                wp_list_comments(array(
                                                    'type' => 'comment',
                                                    'per_page' => $rssOptions['blockCommentsCount'],
                                                    'callback' => 'RFWP_rss_block_comments',
                                                    'end-callback' => 'RFWP_rss_block_comments_end',
                                                    'title_li' => null,
                                                    'max_depth' => $ytcommentsdrevo,
                                                    'reverse_top_level' => $reverse_top_level,
                                                    'reverse_children' => $reverse_children,
                                                    'style' => 'div',
                                                ), $comments);
                                                if ($comments) {echo '</div>';}
    //											if ($comments && $ytad5 == 'enabled' && $ytad5meta != 'disabled') { echo PHP_EOL.'<figure data-turbo-ad-id="fifth_ad_place"></figure>'.PHP_EOL; }
                                                do_action('yturbo_after_comments');
                                            } ?>
                                            ]]>
                                        </turbo:content>
                                        <?php if (!empty($rssOptions['blockRelated'])) {
                                            // hardcoded before all tabs will be created
                                            $hardCoded = 0;
                                            $yttype = 'post';
                                            $tax_query = [];
                                            // end of hardcoded zone

                                            $tempID = $item->ID;
                                            $rcontent = '';

                                            if (!empty($rssOptions['blockRelatedCaching'])) {
                                                $rcontent = get_transient('related-' . $tempID);
                                            }

                                            if(!$rcontent) {
                                                $cats = array();
                                                $childonly = array();
                                                foreach (get_the_category($item->ID) as $cat) {
                                                    array_push($cats, $cat->cat_ID);
                                                    if ($cat->category_parent !== 0 ) {
                                                        array_push($childonly, $cat->cat_ID);
                                                    }
                                                }
                                                if ($childonly) $cats = $childonly;
                                                $cur_post_id = array();
                                                array_push($cur_post_id, $item->ID);

                                                $args = array(
                                                    'post__not_in' => $cur_post_id, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
                                                    'cat' => $cats,
                                                    'orderby' => 'rand',
                                                    'date_query' => array('after' => $rssOptions['blockRelatedDateLimitation'] . ' month ago',),
                                                    'ignore_sticky_posts' => 1,
                                                    'post_type' => $yttype,
                                                    'post_status' => 'publish',
                                                    'posts_per_page' => $rssOptions['blockRelatedCount'],
                                                    'tax_query' => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                                                    'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                                                        'relation' => 'OR',
                                                        array('key' => 'ytrssenabled_meta_value', 'compare' => 'NOT EXISTS',),
                                                        array('key' => 'ytrssenabled_meta_value', 'value' => 'yes', 'compare' => '!=',),
                                                    )
                                                );
                                                $related = new WP_Query( $args );

                                                if (!$related->have_posts()) {
                                                    $args = array(
                                                        'post__not_in' => $cur_post_id, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
                                                        'orderby' => 'rand',
                                                        'date_query' => array('after' => $rssOptions['blockRelatedDateLimitation'].' month ago',),
                                                        'ignore_sticky_posts' => 1,
                                                        'post_type' => $yttype,
                                                        'post_status' => 'publish',
                                                        'posts_per_page' => $rssOptions['blockRelatedCount'],
                                                        'tax_query' => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                                                        'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                                                            'relation' => 'OR',
                                                            array('key' => 'ytrssenabled_meta_value', 'compare' => 'NOT EXISTS',),
                                                            array('key' => 'ytrssenabled_meta_value', 'value' => 'yes', 'compare' => '!=',),
                                                        )
                                                    );
                                                    $related = new WP_Query( $args );
                                                }

                                                if ($related->have_posts()) {
                                                    if (empty($rssOptions['blockRelatedUnstopable'])) {
                                                        $rcontent .= '<yandex:related>'.PHP_EOL;
                                                    } else {
                                                        $rcontent .= '<yandex:related type="infinity">'.PHP_EOL;
                                                    }
                                                }
                                                while ($related->have_posts()) : $related->the_post();
                                                    $ytremove = get_post_meta(get_the_ID(), 'ytremove_meta_value', true);
                                                    if ( $ytremove == 'yes' ) continue;
                                                    $thumburl = '';
                                                    if ($rssOptions['blockRelatedThumb'] != "disable"&& has_post_thumbnail($item->ID)&&empty($rssOptions['blockRelatedUnstopable'])) {
                                                        $thumburl = ' img="' . esc_attr(strtok(get_the_post_thumbnail_url($item->ID,$rssOptions['blockRelatedThumb']), '?')) . '"';
                                                    }
                                                    $rlink = htmlspecialchars(get_the_permalink());
                                                    $rtitle = get_the_title_rss();
                                                    if ($rssOptions['blockRelatedThumb'] != "disable"&&empty($rssOptions['blockRelatedUnstopable'])) {
                                                        $rcontent .=  '<link url="'.esc_url($rlink).'"'.$thumburl.'>'.esc_html($rtitle).'</link>'.PHP_EOL;
                                                    } else {
                                                        $rcontent .=  '<link url="'.esc_url($rlink).'">'.esc_html($rtitle).'</link>'.PHP_EOL;
                                                    }
                                                endwhile;
                                                if ($related->have_posts()) {$rcontent .=  '</yandex:related>'.PHP_EOL;}
                                                if ($related->have_posts()) {
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $rcontent;
                                                }
    //		                                    wp_reset_query($related);
                                                wp_reset_query();

                                                if (!empty($rssOptions['blockRelatedCaching'])) {
                                                    set_transient('related-' . $tempID, $rcontent, $rssOptions['blockRelatedCachelifetime'] * HOUR_IN_SECONDS);
                                                }
                                            } else {
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $rcontent;
                                            }
                                        } else {
                                            ?><yandex:related></yandex:related><?php
                                        } ?>
                                    </item>
                                <?php endforeach; ?>
							<?php endif ?>
                            <?php unset($k,$item); ?>
						</channel>
					</rss>
					<?php
				}
			}
		}
	}
}
catch (Exception $ex) {
	try {
		global $wpdb;

		$messageFLog = 'Deactivation error: '.$ex->getMessage().';';
        RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

		if (!empty($GLOBALS['wpPrefix'])) {
			$wpPrefix = $GLOBALS['wpPrefix'];
		} else {
			global $table_prefix;
			$wpPrefix = $table_prefix;
		}

        RFWP_Utils::saveToRbSettings('realbigForWP: ' . $ex->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

//	include_once ( dirname(__FILE__)."/../../../wp-admin/includes/plugin.php" );
	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($ex); ?></div><?php
}
catch (Error $er) {
	try {
		global $wpdb;

		$messageFLog = 'Deactivation error: '.$er->getMessage().';';
        RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

		if (!empty($GLOBALS['wpPrefix'])) {
			$wpPrefix = $GLOBALS['wpPrefix'];
		} else {
			global $table_prefix;
			$wpPrefix = $table_prefix;
		}

        RFWP_Utils::saveToRbSettings('realbigForWP: ' . $er->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

//	include_once ( dirname(__FILE__)."/../../../wp-admin/includes/plugin.php" );
	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($er); ?></div><?php
}
