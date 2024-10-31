<?php

if (!defined("ABSPATH")) { exit;}

/**
 * Created by PhpStorm.
 * User: user
 * Date: 2018-07-03
 * Time: 17:07
 */

try {
    if (empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON))) {
		if (!function_exists('RFWP_gatheringContentLength')) {
			function RFWP_gatheringContentLength($content, $isRepeated=null) {
				try {
					$contentForLength = '';
					$contentLength = 0;
					$cuttedContent = $content;
					$listOfTags = [];
					$listOfTags['unavailable'] = ['ins','script','style'];
					$listOfTags['available'] = ['p','div','span','blockquote','table','ul','ol','h1','h2','h3','h4','h5','h6','strong','article'];
					$listOfSymbolsForEcranising = '(\/|\$|\^|\.|\,|\&|\||\(|\)|\+|\-|\*|\?|\!|\[|\]|\{|\}|\<|\>|\\\|\~){1}';
//				$listOfSymbolsForEcranising = '[a--z]|(\/|\$|\^|\.|\,|\&|\||\(|\)|\+|\-|\*|\?|\!|\[|\]|\{|\}|\<|\>|\\\|\~){1}';
					if (!function_exists('RFWP_preg_length_warning_handler')) {
						function RFWP_preg_length_warning_handler($errno, $errstr) {
							$messageFLog = 'Test error in content length: errStr - '.$errstr.'; errNum - '.$errno.';';
                            RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
						}
					}
					if (empty($isRepeated)) {
						set_error_handler("RFWP_preg_length_warning_handler", E_WARNING);
						foreach ($listOfTags AS $affiliation => $listItems) {
							for ($lc = 0; $lc < count($listItems); $lc++) {
								$cycler = 1;
								$tg1 = $listItems[$lc];
								$pattern1 = '~(<'.$tg1.'>|<'.$tg1.'\s[^>]*?>)(((?!<'.$tg1.'>)(?!<'.$tg1.'\s[^>]*?>))[\s\S]*?)(<\/'.$tg1.'>)~';
								while (!empty($cycler)) {
									preg_match($pattern1, $cuttedContent, $clMatch);
									if (!empty($clMatch[0])) {
										if ($affiliation == 'available') {
											$contentForLength .= $clMatch[0];
										}
										// if nothing help, change system to array with loop type


										$resItem = preg_replace_callback('~'.$listOfSymbolsForEcranising.'~', function ($matches) {return '\\'.$matches[1];}, $clMatch[0], -1, $crc);
										$cuttedContent = preg_replace_callback('~'.$resItem.'~', function () {return '';}, $cuttedContent, 1,$repCount);

										$cycler = 1;
									} else {
										$cycler = 0;
									}
								}
							}
						}
						restore_error_handler();
						$contentLength = mb_strlen(wp_strip_all_tags($contentForLength), 'utf-8');
						return $contentLength;
					} else {
						return $contentLength;
					}
				} catch (Exception $ex1) {
					$messageFLog = 'Some error in content length: '.$ex1->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					return 0;
				} catch (Error $er1) {
					$messageFLog = 'Some error in content length: '.$er1->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					return 0;
				} catch (E_WARNING $ew) {
					$messageFLog = 'Some error in content length: '.$ew->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					return 0;
				} catch (E_DEPRECATED $ed) {
					$messageFLog = 'Some error in content length: '.$ed->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					return 0;
				}
			}
		}
		if (!function_exists('RFWP_addIcons')) {
			function RFWP_addIcons($fromDb, $content) {
				try {
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'addIcons_test begin');
					}
					global $wp_query;
					global $post;

					$editedContent         = $content;
					$contentLength         = 0;

					$previousEditedContent = $editedContent;
					$usedBlocksCounter     = 0;
					$usedBlocks            = [];
					$rejectedBlocksCounter = 0;
					$rejectedBlocks        = [];
					$objArray              = [];
					$onCategoriesArray     = [];
					$offCategoriesArray    = [];
					$onTagsArray           = [];
					$offTagsArray          = [];
					$pageCategories        = [];
					$pageTags              = [];
					$acceptedBlocksCounter = 0;
					$acceptedBlocks        = [];
					$acceptedBlocksString  = '';
					$rejectedBlocksString  = '';

					if (!empty($fromDb)) {
						/** New system for content length checking **/
						$contentLength = RFWP_gatheringContentLength($content);
						/** End of new system for content length checking **/
						if ($contentLength < 1) {
							$messageFLog = 'Error content length from function;';
                            RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

							$contentLength = mb_strlen(wp_strip_all_tags($content), 'utf-8');
						}
						$contentLengthOld = mb_strlen(wp_strip_all_tags($content), 'utf-8');

						$headersMatchesResult = preg_match_all('~<(h1|h2|h3|h4|h5|h6)~', $content, $headM);
						$headersMatchesResult = count($headM[0]);
						$headersMatchesResult += 1;

//					$pageCategories = RFWP_getPageCategories($pageCategories);
//					$pageTags = RFWP_getPageTags($pageTags);

						foreach ($fromDb AS $k => $item) {
							$countReplaces = 0;
							if (is_object($item)) {
								$item = get_object_vars($item);
							}
							if (empty($item['setting_type'])) {
								$rejectedBlocks[$rejectedBlocksCounter] = $item['id'];
								$rejectedBlocksCounter ++;
								continue;
							}
							if (!empty($headersMatchesResult)) {
								if (!empty($item['minHeaders']) && $item['minHeaders'] > 0 && $item['minHeaders'] > $headersMatchesResult) {
									$rejectedBlocks[$rejectedBlocksCounter] = $item['id'];
									$rejectedBlocksCounter ++;
									continue;
								}
								if (!empty($item['maxHeaders']) && $item['maxHeaders'] > 0 && $item['maxHeaders'] < $headersMatchesResult) {
									$rejectedBlocks[$rejectedBlocksCounter] = $item['id'];
									$rejectedBlocksCounter ++;
									continue;
								}
							}
							if (!empty($item['minSymbols']) && $item['minSymbols'] > 0 && $item['minSymbols'] > $contentLength) {
								$rejectedBlocks[$rejectedBlocksCounter] = $item['id'];
								$rejectedBlocksCounter ++;
								continue;
							}
							if (!empty($item['maxSymbols']) && $item['maxSymbols'] > 0 && $item['maxSymbols'] < $contentLength) {
								$rejectedBlocks[$rejectedBlocksCounter] = $item['id'];
								$rejectedBlocksCounter ++;
								continue;
							}

							/************************************* */
							$rejectedBlockRes = RFWP_onOffCategoryTag($item);
							if (!empty($rejectedBlockRes)) {
								$rejectedBlocks[$rejectedBlocksCounter] = $item['id'];
								$rejectedBlocksCounter ++;
								continue;
							}

							/************************************* */
							if (!empty($editedContent)) {
								$previousEditedContent = $editedContent;
							} else {
								$messageFLog = 'Emptied edited content;';
                                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

								$editedContent = $previousEditedContent;
							}
							$acceptedBlocks[$acceptedBlocksCounter] = $item['id'];
						}
						$finalAcceptedBlocks = '';
						if (count($acceptedBlocks) > 0) {
							$acceptedBlocksString = implode(',',$acceptedBlocks);
							$finalAcceptedBlocks = ' data-accepted-blocks="'.$acceptedBlocksString.'"';
						}
						$finalRejectedBlocks = '';
						if (count($rejectedBlocks) > 0) {
							$rejectedBlocksString = implode(',',$rejectedBlocks);
							$finalRejectedBlocks = ' data-rejected-blocks="'.$rejectedBlocksString.'"';
						}

						$editedContent = '<span class="content_pointer_class" data-content-length="'.$contentLength.'"'.$finalRejectedBlocks.$finalAcceptedBlocks.'></span>'.$editedContent;

						$creatingJavascriptParserForContent = RFWP_creatingJavascriptParserForContentFunction_content();
						$editedContent                      = $editedContent.$creatingJavascriptParserForContent;

						return $editedContent;
					} else {
						return $editedContent;
					}
				} catch (Exception $ex) {
					$messageFLog = 'Some error in addIcons: '.$ex->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

					return $content;
				} catch (Error $er) {
					$messageFLog = 'Some error in addIcons: '.$er->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

					return $content;
				}
			}
		}
		if (!function_exists('RFWP_launch_without_content_function')) {
			function RFWP_launch_without_content_function($content) {
				global $fromDb;
				global $wpdb;
				$wpPrefix = RFWP_getTablePrefix();

				try {
					$adBlocksIdsString = '0';
					$rejectedIdsString = '0';
					$adBlocksIds = [];
					$rejectedIds = [];
					$newContent = '';
					$itemArray = [];
					$checkExcluded = RFWP_checkPageType();
					if (!empty($checkExcluded)&&!empty($fromDb)&&!empty($fromDb['adBlocks'])&&count($fromDb['adBlocks']) > 0) {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$contentSelector = $wpdb->get_var($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` " .
                            "WHERE optionName = %s", "contentSelector"));
						if (empty($contentSelector)) {
							$contentSelector = null;
						}

						foreach ($fromDb['adBlocks'] AS $k => $item) {
							foreach ($item AS $k1 => $item1) {
								$itemArray[$k1] = $item1;
							}
							unset($k1,$item1);
							$rejectedBlockRes = RFWP_onOffCategoryTag($itemArray);
							if (!empty($rejectedBlockRes)) {
								array_push($rejectedIds, $item->id);
							} else {
								array_push($adBlocksIds, $item->id);
							}
						}
						unset($k,$item);
						if (count($adBlocksIds) > 0) {
							$adBlocksIdsString = implode(',', $adBlocksIds);
						}
						if (count($rejectedIds) > 0) {
							$rejectedIdsString = implode(',', $rejectedIds);
						}

						$newContent =
							'<script>
    if (typeof window.jsInputerLaunch === \'undefined\') {
        window.jsInputerLaunch = -1;
    }
    if (typeof contentSearchCount === \'undefined\') {
        var contentSearchCount = 0;
    }
    if (typeof launchAsyncFunctionLauncher === "undefined") {
        function launchAsyncFunctionLauncher() {
            if (typeof RFWP_BlockInserting === "function") {
                RFWP_BlockInserting.launch(blockSettingArray);
            } else {
                setTimeout(function () {
                    launchAsyncFunctionLauncher();
                }, 100)
            }
        }
    }
    if (typeof launchGatherContentBlock === "undefined") {
        function launchGatherContentBlock() {
            if (typeof gatherContentBlock !== "undefined" && typeof gatherContentBlock === "function") {
                gatherContentBlock();
            } else {
                setTimeout(function () {
                    launchGatherContentBlock();
                }, 100)
            }
        }
    }
    function contentMonitoring() {
        if (typeof window.jsInputerLaunch===\'undefined\'||(typeof window.jsInputerLaunch!==\'undefined\'&&window.jsInputerLaunch==-1)) {
            let possibleClasses = [\'.taxonomy-description\',\'.entry-content\',\'.post-wrap\',\'.post-body\',\'#blog-entries\',\'.content\',\'.archive-posts__item-text\',\'.single-company_wrapper\',\'.posts-container\',\'.content-area\',\'.post-listing\',\'.td-category-description\',\'.jeg_posts_wrap\'];
            let deniedClasses = [\'.percentPointerClass\',\'.addedInserting\',\'#toc_container\'];
            let deniedString = "";
            let contentSelector = \''.esc_attr(stripslashes((string)$contentSelector)).'\';
            let contentsCheck = null;
            if (contentSelector) {
                contentsCheck = document.querySelectorAll(contentSelector);
            }

            if (block_classes && block_classes.length > 0) {
                for (var i = 0; i < block_classes.length; i++) {
                    if (block_classes[i]) {
                        deniedClasses.push(\'.\' + block_classes[i]);
                    }
                }
            }

            if (deniedClasses&&deniedClasses.length > 0) {
                for (let i = 0; i < deniedClasses.length; i++) {
                    deniedString += ":not("+deniedClasses[i]+")";
                }
            }
            
            if (!contentsCheck || !contentsCheck.length) {
                for (let i = 0; i < possibleClasses.length; i++) {
                    contentsCheck = document.querySelectorAll(possibleClasses[i]+deniedString);
                    if (contentsCheck.length > 0) {
                        break;
                    }
                }
            }
            if (!contentsCheck || !contentsCheck.length) {
                contentsCheck = document.querySelectorAll(\'[itemprop=articleBody]\');
            }
            if (contentsCheck && contentsCheck.length > 0) {
                contentsCheck.forEach((contentCheck) => {
                    console.log(\'content is here\');
                    let contentPointerCheck = contentCheck.querySelector(\'.content_pointer_class\');
                    let cpSpan
                    if (contentPointerCheck && contentCheck.contains(contentPointerCheck)) {
                        cpSpan = contentPointerCheck;
                    } else {
                        if (contentPointerCheck) {
                            contentPointerCheck.parentNode.removeChild(contentPointerCheck);
                        }
                        cpSpan = document.createElement(\'SPAN\');                    
                    }
                    cpSpan.classList.add(\'content_pointer_class\');
                    cpSpan.classList.add(\'no-content\');
                    cpSpan.setAttribute(\'data-content-length\', \'0\');
                    cpSpan.setAttribute(\'data-accepted-blocks\', \'\');
                    cpSpan.setAttribute(\'data-rejected-blocks\', \'\');
                    window.jsInputerLaunch = 10;
                    
                    if (!cpSpan.parentNode) contentCheck.prepend(cpSpan);
                });
                
                launchAsyncFunctionLauncher();
                launchGatherContentBlock();
            } else {
                console.log(\'contentMonitoring try\');
                if (document.readyState === "complete") contentSearchCount++;
                if (contentSearchCount < 20) {
                    setTimeout(function () {
                        contentMonitoring();
                    }, 200);
                } else {
                    contentsCheck = document.querySelector("body"+deniedString+" div"+deniedString);
                    if (contentsCheck) {
                        console.log(\'content is here hard\');
                        let cpSpan = document.createElement(\'SPAN\');
                        cpSpan.classList.add(\'content_pointer_class\');
                        cpSpan.classList.add(\'no-content\');
                        cpSpan.classList.add(\'hard-content\');
                        cpSpan.setAttribute(\'data-content-length\', \'0\');
                        cpSpan.setAttribute(\'data-accepted-blocks\', \''.esc_attr(stripslashes($adBlocksIdsString)).'\');
                        cpSpan.setAttribute(\'data-rejected-blocks\', \''.esc_attr(stripslashes($rejectedIdsString)).'\');
                        window.jsInputerLaunch = 10;
                        
                        contentsCheck.prepend(cpSpan);
                        launchAsyncFunctionLauncher();
                    }   
                }
            }
        } else {
            console.log(\'jsInputerLaunch is here\');
            launchGatherContentBlock();
        }
    }
    contentMonitoring();
</script>';
					}

					return $newContent;
				} catch (Exception $ex) {
					$messageFLog = 'Some error in RFWP_launch_without_content_function: '.$ex->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					return $content;
				} catch (Error $er) {
					$messageFLog = 'Some error in RFWP_launch_without_content_function: '.$er->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					return $content;
				}
			}
		}
		if (!function_exists('RFWP_onOffCategoryTag')) {
			function RFWP_onOffCategoryTag($item) {
				/** true = block rejected */
				$passAllowed = false;
				$passRejected = false;

				$pageCategories = RFWP_getPageCategories();
				$pageTags = RFWP_getPageTags();

				if (!empty($item['onCategories'])) {
					if (empty($pageCategories)) {
						return true;
					}
					$onCategoriesArray = explode(':',trim($item['onCategories']));
					if (!empty($onCategoriesArray)&&count($onCategoriesArray) > 0) {
						foreach ($onCategoriesArray AS $k1 => $item1) {
							$currentCategory = trim($item1);
							$currentCategory = strtolower($currentCategory);

							if (in_array($currentCategory, $pageCategories['names'])||in_array($currentCategory, $pageCategories['terms'])) {
								$passAllowed = true;
								break;
							}
						}
						unset($k1,$item1);
						if (empty($passAllowed)) {
							return true;
						}
					}
				} elseif (!empty($item['offCategories'])&&!empty($pageCategories)) {
					$offCategoriesArray = explode(':',trim($item['offCategories']));
					if (!empty($offCategoriesArray)&&count($offCategoriesArray) > 0) {
						foreach ($offCategoriesArray AS $k1 => $item1) {
							$currentCategory = trim($item1);
							$currentCategory = strtolower($currentCategory);

							if (in_array($currentCategory, $pageCategories['names'])||in_array($currentCategory, $pageCategories['terms'])) {
								$passRejected = true;
								break;
							}
						}
						unset($k1,$item1);
						if (!empty($passRejected)) {
							return true;
						}
					}
				}
				/************************************* */
				$passAllowed = false;
				$passRejected = false;

				if (!empty($item['onTags'])) {
					if (empty($pageTags)) {
						return true;
					}
					$onTagsArray = explode(':',trim($item['onTags']));
					if (!empty($onTagsArray)&&count($onTagsArray) > 0) {
						foreach ($onTagsArray AS $k1 => $item1) {
							$currentTag = trim($item1);
							$currentTag = strtolower($currentTag);

							if (in_array($currentTag, $pageTags['names'])||in_array($currentTag, $pageTags['terms'])) {
								$passAllowed = true;
								break;
							}
						}
						unset($k1,$item1);
						if (empty($passAllowed)) {
							return true;
						}
					}
				} elseif (!empty($item['offTags'])&&!empty($pageTags)) {
					$offTagsArray = explode(':',trim($item['offTags']));
					if (!empty($offTagsArray)&&count($offTagsArray) > 0) {
						foreach ($offTagsArray AS $k1 => $item1) {
							$currentTag = trim($item1);
							$currentTag = strtolower($currentTag);

							if (in_array($currentTag, $pageTags['names'])||in_array($currentTag, $pageTags['terms'])) {
								$passRejected = true;
								break;
							}
						}
						unset($k1,$item1);
						if (!empty($passRejected)) {
							return true;
						}
					}
				}
				return false;
			}
		}
		if (!function_exists('RFWP_getPageCategories')) {
			function RFWP_getPageCategories() {
				$pageCategories = [];
				if (!empty($GLOBALS['pageCategories'])) {
					$pageCategories = $GLOBALS['pageCategories'];
				} else {
				    $taxonomies = RFWP_getUsedTaxonomiesFromDB();
					$getPageCategories = [];
				    $term = get_queried_object();

				    if (is_a($term, 'WP_Term')) {
					    $getPageCategories = [$term];
                    } elseif (is_a($term, 'WP_Post')) {
					    $getPageCategories = get_the_category(get_the_ID());

					    if (empty($getPageCategories))
					    {
						    $getPageCategories = [];
					    }

				        if (!empty($taxonomies['categories'])) {
				            foreach ($taxonomies['categories'] as $category) {
				                $terms = get_the_terms(get_the_ID(), $category);
				                if (!empty($terms) && !is_wp_error($terms)) {
					                foreach ($terms as $item) {
						                array_push($getPageCategories, $item);
					                }
				                }
                            }
                        }
				    }
					if (!empty($getPageCategories)) {
						$ctCounter = 0;
						$pageCategories['names'] = [];
						$pageCategories['terms'] = [];

						foreach ($getPageCategories AS $k1 => $item1) {
							$item1->name = trim($item1->name);
							$item1->name = strtolower($item1->name);
							$pageCategories['names'][$ctCounter] = $item1->name;
							$pageCategories['terms'][$ctCounter] = $item1->term_id;
							$ctCounter++;
						}
						unset($k1,$item1);
						$GLOBALS['pageCategories'] = $pageCategories;
					}
				}

				return $pageCategories;
			}
		}
		if (!function_exists('RFWP_getPageTags')) {
			function RFWP_getPageTags() {
				$pageTags = [];
				if (!empty($GLOBALS['pageTags'])) {
					$pageTags = $GLOBALS['pageTags'];
				} else {
					$taxonomies = RFWP_getUsedTaxonomiesFromDB();
					$getPageTags = [];
					$term = get_queried_object();

					if (is_a($term, 'WP_Term')) {
						$getPageTags = [$term];
					} elseif (is_a($term, 'WP_Post')) {
						$getPageTags = get_the_tags(get_the_ID());

						if (empty($getPageTags))
						{
						    $getPageTags = [];
                        }

						if (!empty($taxonomies['tags'])) {
							foreach ($taxonomies['tags'] as $tag) {
								$terms = get_the_terms(get_the_ID(), $tag);
								if (!empty($terms) && !is_wp_error($terms)) {
									foreach ($terms as $item) {
										array_push($getPageTags, $item);
									}
								}
							}
						}
					}
					if (!empty($getPageTags)) {
						$ctCounter = 0;
						$pageTags['names'] = [];
						$pageTags['terms'] = [];

						foreach ($getPageTags AS $k1 => $item1) {
							$item1->name = trim($item1->name);
							$item1->name = strtolower($item1->name);
							$pageTags['names'][$ctCounter] = $item1->name;
							$pageTags['terms'][$ctCounter] = $item1->term_id;
							$ctCounter++;
						}
						unset($k1,$item1);
						$GLOBALS['pageTags'] = $pageTags;
					}
				}

				return $pageTags;
			}
		}
		if (!function_exists('RFWP_rbCacheGatheringLaunch')) {
			function RFWP_rbCacheGatheringLaunch($content) {
			    if (!empty($GLOBALS['rfwp_is_amp'])) {
			        return $content;
                }

				global $wpdb;

			    $cachedBlocks = get_posts(['post_type' => 'rb_block_' . $GLOBALS['rb_type_device'] . '_new','posts_per_page' => 100]);
			    $longCache = RFWP_Cache::getLongCache();
			    $content = RFWP_rb_cache_gathering($content, $cachedBlocks, $longCache);

				return $content;
			}
		}
		if (!function_exists('RFWP_shortCodesAdd')) {
			function RFWP_shortCodesAdd($content) {
				if (empty($GLOBALS['rfwp_shortcodes'])) {
					RFWP_shortcodesInGlobal();
				}
				if (!empty($GLOBALS['rfwp_shortcodes'])&&$GLOBALS['rfwp_shortcodes']!='nun') {
					$content = RFWP_shortcodesToContent($content);
				}
				return $content;
			}
		}
		if (!function_exists('RFWP_shortcodesToContent')) {
			function RFWP_shortcodesToContent($content) {
				try {
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'shortcodesToContent begin');
					}
					$scContent = '';
					global $rfwp_shortcodes;
					if (!empty($rfwp_shortcodes)&&$rfwp_shortcodes!='nun'&&count($rfwp_shortcodes) > 0) {
						$scContent .= '<script>'.PHP_EOL;
						$scContent .= 'var scArray = [];'.PHP_EOL;
						$cou = 0;
						foreach ($rfwp_shortcodes AS $k1 => $item1) {
//		        $scContent .= 'scArray['.$k1.'] = [];'.PHP_EOL;
							foreach ($item1 AS $k2 => $item2) {
								$scContent .= 'scArray['.$cou.'] = [];'.PHP_EOL;
								$scContent .= 'scArray['.$cou.']["blockId"] = '.$k1.';'.PHP_EOL;
								$scContent .= 'scArray['.$cou.']["adId"] = '.$k2.';'.PHP_EOL;
								$scContent .= 'scArray['.$cou.']["fetched"] = 0;'.PHP_EOL;
								$scText = $item2;
								$scText = preg_replace('~(\'|\")~','\\\$1',$scText);
								$scText = preg_replace('~(\\\u|\\\n|\\\r)~','\\\$1',$scText);
								$scText = preg_replace('~(\r\n|\n|\r)~',' ',$scText);
								$scText = preg_replace('~\<script~', '<scr"+"ipt', $scText);
								$scText = preg_replace('~\/script~', '/scr"+"ipt', $scText);
								$scContent .= 'scArray['.$cou.']["text"] = "'.$scText.'";'.PHP_EOL;
//			        $scContent .= 'scArray['.$k1.']['.$k2.'] = "'.$scText.'";'.PHP_EOL;
								$cou++;
							}
							unset($k2,$item2);
						}
						unset($k1,$item1);
						$scContent .= '</script>'.PHP_EOL;
						$content = $content.$scContent;
					}
					return $content;
				} catch (Exception $ex) {
					$messageFLog = 'Some error in shortcodesToContent: '.$ex->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					return $content;
				} catch (Error $er) {
					$messageFLog = 'Some error in shortcodesToContent: '.$er->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					return $content;
				}
			}
		}
		/** Add rotator file to header */
		if (!function_exists('RFWP_rotatorToHeaderAdd')) {
			function RFWP_rotatorToHeaderAdd() {
				RFWP_launch_cache_local($GLOBALS['rb_variables']['rotator'], $GLOBALS['rb_variables']['adDomain']);
				$pluginVersion = RFWP_Utils::getVersion();
				$src = $GLOBALS['rb_variables']['localRotatorUrl'];
				if (!empty($pluginVersion)) {
                    $src = $src.'?ver='.$pluginVersion;
                }
				/* ?><script>let penyok_stoparik = 0;</script><?php /**/
				?><script type="text/javascript" src="<?php echo esc_url($src); ?>" id="<?php echo esc_attr($GLOBALS['rb_variables']['rotator']); ?>-js" async=""></script><?php /**/
//				wp_enqueue_script(
//					$GLOBALS['rb_variables']['rotator'],
//					plugins_url().'/'.basename(__DIR__).'/'.$GLOBALS['rb_variables']['rotator'].'.js',
//					array('jquery'),
//					$GLOBALS['realbigForWP_version'],
//					false
//				);

				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'rotatorToHeaderAdd rotator file added');
				}
			}
		}
		/** End of Add rotator file to header */
	}

	if (!function_exists('RFWP_excludedPagesAndDuplicates')) {
	    function RFWP_excludedPagesAndDuplicates() {
		    global $wpdb;

		    $result['excIdClass'] = null;
		    $result['blockDuplicate'] = 'yes';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		    $realbig_settings_info = $wpdb->get_results($wpdb->prepare("SELECT optionName, optionValue FROM `{$GLOBALS['wpPrefix']}realbig_settings` WGPS " .
                "WHERE optionName IN (%s, %s)", "excludedIdAndClasses", "blockDuplicate"));
		    if (!empty($realbig_settings_info)) {
			    foreach ($realbig_settings_info AS $k => $item) {
				    if (isset($item->optionValue)) {
					    if ($item->optionName == 'excludedIdAndClasses') {
						    $result['excIdClass'] = $item->optionValue;
					    } elseif ($item->optionName == 'blockDuplicate') {
						    if ($item->optionValue==0) {
							    $result['blockDuplicate'] = 'no';
						    }
					    }
				    }
			    }
			    unset($k,$item);
		    }
		    return $result;
        }
	}
	if (!function_exists('RFWP_shortcodesInGlobal')) {
	    function RFWP_shortcodesInGlobal() {
		    try {
			    $shortcodesGathered = get_posts(['post_type' => 'rb_shortcodes','numberposts' => -1]);
			    if (empty($shortcodesGathered)) {
			        $GLOBALS['rfwp_shortcodes'] = 'nun';
		        } else {
			        $shortcodes = [];
			        foreach ($shortcodesGathered AS $k=>$item) {
				        if (empty($shortcodes[$item->post_excerpt])) {
					        $shortcodes[$item->post_excerpt] = [];
				        }
				        $activatedCode = do_shortcode($item->post_content);
				        $shortcodes[$item->post_excerpt][$item->post_title] = $activatedCode;
			        }
			        $GLOBALS['rfwp_shortcodes'] = $shortcodes;
                }
		        if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
			        RFWP_WorkProgressLog(false,'RFWP_shortcodesInGlobal end');
		        }

		        return true;
            } catch (Exception $ex) {
		        $messageFLog = 'Some error in shortcodesInGlobal: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
		        return false;
	        } catch (Error $er) {
		        $messageFLog = 'Some error in shortcodesInGlobal: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
		        return false;
	        }
	    }
    }
    if (!function_exists('RFWP_rb_cache_gathering')) {
        function RFWP_rb_cache_gathering($content, $cachedBlocks, $longCache) {
	        try {
		        $usedBlockIds = [];
		        $scriptString = '';
		        $scriptString .= '<script>'.PHP_EOL;
		        $scriptString .= 'var cachedBlocksArray = [];'.PHP_EOL;

		        foreach ($cachedBlocks AS $k => $item) {
			        if (in_array($item->post_title, $usedBlockIds)) {
				        continue;
			        }
			        array_push($usedBlockIds, $item->post_title);

			        $scriptString .= 'cachedBlocksArray[' . $item->post_title . '] = ' .
                        RFWP_rb_cache_gathering_content($item->post_content, true) . ';' . PHP_EOL;
		        }
		        if (!empty($longCache)) {
			        $scriptString .=
				        'function onErrorPlacing() {
                            if (typeof cachePlacing !== \'undefined\' && typeof cachePlacing === \'function\') {
                                cachePlacing("low");
                            } else {
                                setTimeout(function () {
                                    onErrorPlacing();
                                }, 100)
                            }
                        }
                        onErrorPlacing();';
		        }

		        $scriptString .= '</script>';
		        $content = $content.$scriptString;
		        return $content;
            } catch (Exception $ex) {
	            $messageFLog = 'Some error in rb_cache_gathering: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
	            return $content;
            } catch (Error $er) {
	            $messageFLog = 'Some error in rb_cache_gathering: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
	            return $content;
            }
		}
	}
    if (!function_exists('RFWP_rb_cache_gathering_content')) {
        function RFWP_rb_cache_gathering_content($content, $encode = false) {
            $content = do_shortcode($content);

            $content = preg_replace('~corner_open;~ius', '<',$content);
            $content = preg_replace('~corner_close;~ius', '>',$content);
            $content = preg_replace('~<scr_pt_open;~ius', '<script',$content);
            $content = preg_replace('~/scr_pt_close;~ius', '/script',$content);

            if ($encode) {
                $content = wp_json_encode($content, JSON_UNESCAPED_UNICODE);
                $content = preg_replace('~<script~ius', '<scr"+"ipt',$content);
                $content = preg_replace('~/script~ius', '/scr"+"ipt',$content);
                $content = preg_replace('~(\r\n|\n|\r)~ius',' ',$content);
            }

            return $content;
        }
    }
	if (!function_exists('RFWP_wp_get_type_device')) {
		function RFWP_wp_get_type_device() {
			try {
				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'wp_is_mobile begin');
				}

				$tablet_browser = 0;
				$mobile_browser = 0;

				if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
					$tablet_browser++;
				}

				if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
					$mobile_browser++;
				}

				if ((isset($_SERVER['HTTP_ACCEPT']) && strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) ||
                    ((isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])))) {
					$mobile_browser++;
				}

				$mobile_ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4)) : "";
				$mobile_agents = array(
					'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
					'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
					'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
					'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
					'newt','noki','palm','pana','pant','phil','play','port','prox',
					'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
					'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
					'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
					'wapr','webc','winw','winw','xda ','xda-');

				if (in_array($mobile_ua,$mobile_agents)) {
					$mobile_browser++;
				}

				if (isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'opera mini') > 0) {
					$mobile_browser++;
					//Check for tablets on opera mini alternative headers
					$stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])?$_SERVER['HTTP_X_OPERAMINI_PHONE_UA']:(isset($_SERVER['HTTP_DEVICE_STOCK_UA'])?$_SERVER['HTTP_DEVICE_STOCK_UA']:''));
					if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
						$tablet_browser++;
					}
				}

				if ($tablet_browser > 0) {
					$type = 'tablet';
				} else if ($mobile_browser > 0) {
					$type = 'mobile';
				} else {
					$type = 'desktop';
				}

			    return $type;
            } catch (Exception $ex) {
			    $messageFLog = 'Some error in wp_is_mobile: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			    return false;
		    } catch (Error $er) {
			    $messageFLog = 'Some error in wp_is_mobile: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			    return false;
		    }
		}
	}
	if (!function_exists('RFWP_headerInsertor')) {
		function RFWP_headerInsertor($patternType) {
			try {
                include_once(plugin_dir_path(__FILE__) . "RFWP_Variables.php");

			    $detectedHeader = false;
				if ($patternType=='ad') {
					$checkedHeaderPattern = '~rbConfig=(\s|\r\n|\n|\r)*?\{start\:performance\.now\(\)~iu';
				} elseif ($patternType=='push') {
					$checkedHeaderPattern = '~\<script\s+?.*?src\s*?=\s*?["\']{1}[^"\']+?\/pushJs\/[^"\']+?["\']{1}[^>]*?\>\<\/script\>~iu';
				} elseif ($patternType=='pushUniversal') {
					$checkedHeaderPattern = '~\<script\s+?.*?src\s*?=\s*?["\']{1}[^"\']+?\/pjs\/[^"\']+?["\']{1}[^>]*?\>\<\/script\>~iu';
				} elseif ($patternType=='pushNative') {
					$checkedHeaderPattern = '~\<script\s+?.*?src\s*?=\s*?["\']{1}[^"\']+?\/nat\/[^"\']+?["\']{1}[^>]*?\>\<\/script\>~iu';
				} else {
					return false;
				}
                $result = true;

				$themeHeaderFileOpen = false;
				$wp_cur_theme_name = get_stylesheet();
				if (!empty($wp_cur_theme_name)) {
                    if (!empty($wp_cur_theme_root)) {
                        $themeHeaderFileCheck = file_exists(get_theme_root().'/'.$wp_cur_theme_name.'/header.php');
                        if ($themeHeaderFileCheck) {
                            //@codingStandardsIgnoreStart
                            $themeHeaderFileOpen = file_get_contents(get_theme_root().'/'.$wp_cur_theme_name.'/header.php');
                            //@codingStandardsIgnoreEnd
                        }
                    }
                    if (empty($themeHeaderFileOpen)) {
                        $themeHeaderFileCheck = file_exists(plugin_dir_path(__FILE__).'.../.../themes/'.$wp_cur_theme_name.'/header.php');
                        if ($themeHeaderFileCheck) {
                            $themeHeaderFileOpen = file_get_contents(plugin_dir_path(__FILE__).'.../.../themes/'.$wp_cur_theme_name.'/header.php');
                        }
                    }
                    if (empty($themeHeaderFileOpen)) {
                        $themeHeaderFileCheck = file_exists(ABSPATH.'wp-content/themes/'.$wp_cur_theme_name.'/header.php');
                        if ($themeHeaderFileCheck) {
                            $themeHeaderFileOpen = file_get_contents(ABSPATH.'wp-content/themes/'.$wp_cur_theme_name.'/header.php');
                        }
                    }

                    $checkRebootInName = preg_match('~reboot~', $wp_cur_theme_name, $rm);
                    if (count($rm) > 0) {
                        $rebootHeaderGet = get_option('reboot_options');
                        if (!empty($rebootHeaderGet)&&!empty($rebootHeaderGet['code_head'])) {
                            $checkedHeader = preg_match($checkedHeaderPattern, $rebootHeaderGet['code_head'], $rm1);
                            if (count($rm1) == 0) {
                                ?><script>console.log('reboot <?php echo esc_html($patternType) ?>: nun')</script><?php
                                $result = true;
                            } else {
                                ?><script>console.log('reboot <?php echo esc_html($patternType) ?>: presents')</script><?php
                                $result = false;
                                $detectedHeader = true;
                            }
                        } else {
                            ?><script>console.log('reboot <?php echo esc_html($patternType) ?>: options error')</script><?php
                        }
                    }

                    if (empty($detectedHeader)) {
                        if (!empty($themeHeaderFileOpen)) {
                            $checkedHeader = preg_match($checkedHeaderPattern, $themeHeaderFileOpen, $m);
                            if (count($m) == 0) {
                                ?><script>console.log('<?php echo esc_html($patternType) ?>: nun')</script><?php
                                $result = true;
                            } else {
                                ?><script>console.log('<?php echo esc_html($patternType) ?>: presents')</script><?php
                                $result = false;
                            }
                        } else {
                            ?><script>console.log('<?php echo esc_html($patternType) ?>: header error')</script><?php
                            $result = true;
                        }
                    }
                }

				return $result;
			} catch (Exception $ex) {
				$messageFLog = 'Some error in headerInsertor: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return true;
			} catch (Error $er) {
				$messageFLog = 'Some error in headerInsertor: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return true;
			}
		}
	}
	/** Insertings to end of content adding **********/
	if (!function_exists('RFWP_insertingsToContent')) {
		function RFWP_insertingsToContent($content) {
			try {
			    if (empty($GLOBALS['addInsertings']['body']['data'])) {
				    return $content;
			    }

			    $jsScriptString = '';
			    $cssScriptString = '';
			    $currentItemContent = '';
			    $insertings = $GLOBALS['addInsertings']['body']['data'];
			    $counter = 0;

			    if (!empty($insertings)) {
				    $cssScriptString .= '<style>
    .coveredInsertings {
/*        max-height: 1px;
          max-width: 1px; */
    }
</style>';

				    $jsScriptString .= '<script>'.PHP_EOL;
				    $jsScriptString .= 'var insertingsArray = [];'.PHP_EOL;
				    // move blocks in lopp and add to js string
				    foreach ($insertings AS $k=>$item) {
					    if (!empty($item['content'])) {
						    if (empty($item['position_element'])) {
							    $content .= '<div class="addedInserting">'.$item['content'].'</div>';
						    } else {
							    $content .= '<div class="addedInserting coveredInsertings" data-id="'.$item['postId'].'">'.$item['content'].'</div>';

							    $jsScriptString .= 'insertingsArray['.$k.'] = [];'.PHP_EOL;
							    $jsScriptString .= 'insertingsArray['.$k.'][\'position_element\'] = "'.$item['position_element'].'"'.PHP_EOL;
							    $jsScriptString .= 'insertingsArray['.$k.'][\'position\'] = "'.$item['position'].'"'.PHP_EOL;
							    $jsScriptString .= 'insertingsArray['.$k.'][\'postId\'] = "'.$item['postId'].'"'.PHP_EOL;

							    $counter++;
						    }
					    }
				    }
				    $jsScriptString .= 'var jsInsertingsLaunch = 25;'.PHP_EOL;
				    $jsScriptString .=
					    'function launchInsertingsFunctionLaunch() {
    if (typeof insertingsFunctionLaunch !== \'undefined\' && typeof insertingsFunctionLaunch === \'function\') {
        insertingsFunctionLaunch();
    } else {
        setTimeout(function () {
            launchInsertingsFunctionLaunch();
        }, 100)
    }
}
launchInsertingsFunctionLaunch();'.PHP_EOL;

				    $jsScriptString .= '</script>';

				    $content .= $cssScriptString.$jsScriptString;
			    }

			    return $content;
            } catch (Exception $ex) {
			    $messageFLog = 'Some error in insertingsToContent: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			    return $content;
		    } catch (Error $er) {
			    $messageFLog = 'Some error in insertingsToContent: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			    return $content;
		    }
		}
	}
	/** End of insertings to end of content adding ***/
	if (!function_exists('RFWP_insertsToString')) {
		function RFWP_insertsToString($type, $filter=null) {
		    global $wpdb;
			$result = [];
//			$result['header'] = [];
//			$result['body'] = [];
			if (!empty($GLOBALS['wpPrefix'])) {
				$wpPrefix = $GLOBALS['wpPrefix'];
			} else {
				global $table_prefix;
				$wpPrefix = $table_prefix;
			}
			if (!is_admin() && empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON))
                && empty(apply_filters('wp_doing_ajax', defined('DOING_AJAX') && DOING_AJAX))) {
				RFWP_WorkProgressLog(false,'insertsToString begin');
			}

			try {
                // @codingStandardsIgnoreStart
				if (isset($filter)&&in_array($filter, [0,1])) {
					$posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpPrefix}posts` WHERE post_type = %s AND pinged = %s",
                        "rb_inserting", $filter));
				} else {
					$posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpPrefix}posts` WHERE post_type = %s",
                        "rb_inserting"));
				}
                // @codingStandardsIgnoreEnd
				if (!empty($posts)) {
					if ($type=='header') {
						if (!empty($GLOBALS['addInsertings']['header']['insertsCounter'])) {
							$counter = $GLOBALS['addInsertings']['header']['insertsCounter'];
						} else {
							$counter = 0;
						}
						if (!empty($GLOBALS['addInsertings']['header']['data'])) {
							$result = $GLOBALS['addInsertings']['header']['data'];
						}
						foreach ($posts AS $k=>$item) {
							$result[$counter] = [];
							$gatheredHeader = $item->post_content;
							$gatheredHeader = preg_match('~begin_of_header_code([\s\S]*?)end_of_header_code~',$gatheredHeader,$headerMatches);
							$gatheredHeader = htmlspecialchars_decode($headerMatches[1]);
							$result[$counter]['content'] = $gatheredHeader;
							$counter++;
						}
						$GLOBALS['addInsertings']['header']['insertsCounter'] = $counter;
						$GLOBALS['addInsertings']['header']['data'] = $result;
					} else {
						if (!empty($GLOBALS['addInsertings']['body']['insertsCounter'])) {
							$counter = $GLOBALS['addInsertings']['body']['insertsCounter'];
						} else {
							$counter = 0;
						}
						if (!empty($GLOBALS['addInsertings']['body']['data'])) {
							$result = $GLOBALS['addInsertings']['body']['data'];
						}
						foreach ($posts AS $k=>$item) {
							$result[$counter] = [];
							$gatheredBody = $item->post_content;
							$gatheredBody = preg_match('~begin_of_body_code([\s\S]*?)end_of_body_code~',$gatheredBody,$bodyMatches);
							$gatheredBody = htmlspecialchars_decode($bodyMatches[1]);
							$gatheredBody = do_shortcode($gatheredBody);
							$result[$counter]['content'] = $gatheredBody;
							$result[$counter]['position_element'] = $item->post_title;
							$result[$counter]['position'] = $item->post_excerpt;
							$result[$counter]['postId'] = $item->ID;
							$counter++;
						}
						$GLOBALS['addInsertings']['body']['insertsCounter'] = $counter;
						$GLOBALS['addInsertings']['body']['data'] = $result;
					}
				}
			} catch (Exception $ex) {
				$messageFLog = 'Some error in insertsToString: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			} catch (Error $er) {
				$messageFLog = 'Some error in insertsToString: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			}
			return $result;
		}
	}
	if (!function_exists('RFWP_creatingJavascriptParserForContentFunction_content')) {
		function RFWP_creatingJavascriptParserForContentFunction_content() {
			try {
				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'creatingJavascriptParserForContentFunction_content begin');
				}
				$scriptingCode = '<script>'.PHP_EOL;
//				$scriptingCode .= 'if (typeof contentLength==="undefined") {var contentLength = '.$contentLength.';} else {contentLength = '.$contentLength.';}'.PHP_EOL;
				$scriptingCode .= 'window.jsInputerLaunch = 15;'.PHP_EOL;
				$scriptingCode .=
'if (typeof launchAsyncFunctionLauncher === "undefined") {
    function launchAsyncFunctionLauncher() {
        if (typeof RFWP_BlockInserting === "function") {
            RFWP_BlockInserting.launch(blockSettingArray);
        } else {
            setTimeout(function () {
                launchAsyncFunctionLauncher();
            }, 100)
        }
    }
}
launchAsyncFunctionLauncher();'.PHP_EOL;

				$scriptingCode .= '</script>';

				return $scriptingCode;
			} catch (Exception $ex) {
				$messageFLog = 'Some error in creatingJavascriptParserForContent: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return '';
			} catch (Error $er) {
				$messageFLog = 'Some error in creatingJavascriptParserForContent: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return '';
			}
		}
	}
	if (!function_exists('RFWP_creatingJavascriptParserForContentFunction_test')) {
		function RFWP_creatingJavascriptParserForContentFunction_test($fromDb, $excIdClass, $blockDuplicate, $obligatoryMargin, $tagsListForTextLength, $showAdsNoElement) {
			try {
				/*?><script>console.log('Header addings passed');</script><?php*/
				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'creatingJavascriptParserForContentFunction_test begin');
				}
				$cou1 = 0;
				global $rfwp_shortcodes;
				$scriptingCodeResult = [];
				$contentBeforeScript = ''.PHP_EOL;
				$cssCode = ''.PHP_EOL;
				$cssCode .='<style>
    .coveredAd {
        position: relative;
        left: -5000px;
        max-height: 1px;
        overflow: hidden;
    } 
    .content_pointer_class {
        display: block !important;
        width: 100% !important;
    }
    .rfwp_removedMarginTop {
        margin-top: 0 !important;
    }
    .rfwp_removedMarginBottom {
        margin-bottom: 0 !important;
    }
</style>';
				$scriptingCode = '
            <script>
            var cou1 = 0;
            if (typeof blockSettingArray==="undefined") {
                var blockSettingArray = [];
            } else {
                if (Array.isArray(blockSettingArray)) {
                    cou1 = blockSettingArray.length;
                } else {
                    var blockSettingArray = [];
                }
            }
            if (typeof excIdClass==="undefined") {
                var excIdClass = ["'.$excIdClass.'"];
            }
            if (typeof blockDuplicate==="undefined") {
                var blockDuplicate = "'.$blockDuplicate.'";
            }                        
            if (typeof obligatoryMargin==="undefined") {
                var obligatoryMargin = '.intval($obligatoryMargin).';
            }
            ';
				if (!empty($tagsListForTextLength)) {
					$scriptingCode .= '
            if (typeof tagsListForTextLength==="undefined") {
                var tagsListForTextLength = ["'.$tagsListForTextLength.'"];
            }                        
            ';
                }

				$k1 = 0;
				foreach ($fromDb AS $k => $item) {
					$resultHere = 'normal';
					if (is_object($item)) {
						$item = get_object_vars($item);
					}
//					$resultHere = in_array($item['id'], $usedBlocks);
//					if ($resultHere == false) {
					if ($resultHere == 'normal') {
//				    $contentBeforeScript .= $item['text'].PHP_EOL;
						$scriptingCode .= 'blockSettingArray[cou1] = [];'.PHP_EOL;

						if (!empty($item['minSymbols'])&&$item['minSymbols'] > 1) {
							$scriptingCode .= 'blockSettingArray[cou1]["minSymbols"] = '.$item['minSymbols'].'; '.PHP_EOL;
						} else {
							$scriptingCode .= 'blockSettingArray[cou1]["minSymbols"] = 0;'.PHP_EOL;
						}
						if (!empty($item['maxSymbols'])&&$item['maxSymbols'] > 1) {
							$scriptingCode .= 'blockSettingArray[cou1]["maxSymbols"] = '.$item['maxSymbols'].'; '.PHP_EOL;
						} else {
							$scriptingCode .= 'blockSettingArray[cou1]["maxSymbols"] = 0;'.PHP_EOL;
						}
						if (!empty($item['minHeaders'])&&$item['minHeaders'] > 1) {
							$scriptingCode .= 'blockSettingArray[cou1]["minHeaders"] = '.$item['minHeaders'].'; '.PHP_EOL;
						} else {
							$scriptingCode .= 'blockSettingArray[cou1]["minHeaders"] = 0;'.PHP_EOL;
						}
						if (!empty($item['maxHeaders'])&&$item['maxHeaders'] > 1) {
							$scriptingCode .= 'blockSettingArray[cou1]["maxHeaders"] = '.$item['maxHeaders'].'; '.PHP_EOL;
						} else {
							$scriptingCode .= 'blockSettingArray[cou1]["maxHeaders"] = 0;'.PHP_EOL;
						}
                        if (isset($item['showNoElement'])) {
                            $scriptingCode .= 'blockSettingArray[cou1]["showNoElement"] = '.$item['showNoElement'].'; '.PHP_EOL;
                        } else {
                            $scriptingCode .= 'blockSettingArray[cou1]["showNoElement"] = '.$showAdsNoElement.';'.PHP_EOL;
                        }
						$scriptingCode     .= 'blockSettingArray[cou1]["id"] = \''.$item['id'].'\'; '.PHP_EOL;
						if (!empty($rfwp_shortcodes[$item['block_number']])) {
							$scriptingCode .= 'blockSettingArray[cou1]["sc"] = \'1\'; '.PHP_EOL;
						} else {
							$scriptingCode .= 'blockSettingArray[cou1]["sc"] = \'0\'; '.PHP_EOL;
						}
						$currentItemContent = $item['text'];
						$currentItemContent = preg_replace('~(\'|\")~','\\\$1',$currentItemContent);
						$currentItemContent = preg_replace('~(\r\n)~','',$currentItemContent);
//					$currentItemContent = preg_replace('~(\<\/script\>)~','</scr"+"ipt>',$currentItemContent);
//					$scriptingCode     .= 'blockSettingArray[cou1]["text"] = \'' . $item['text'] . '\'; ' . PHP_EOL;
						$scriptingCode     .= 'blockSettingArray[cou1]["text"] = \'' . $currentItemContent . '\'; ' . PHP_EOL;
						$scriptingCode     .= 'blockSettingArray[cou1]["setting_type"] = '.$item['setting_type'].'; ' . PHP_EOL;
						$scriptingCode     .= 'blockSettingArray[cou1]["rb_under"] = ' . wp_rand(100000, 1000000) . '; ' . PHP_EOL;
//						$scriptingCode     .= 'blockSettingArray[cou1]["block_number"] = '.$item['block_number'].'; ' . PHP_EOL;
						if (!empty($item['elementCss'])) {
							$scriptingCode     .= 'blockSettingArray[cou1]["elementCss"] = "'.$item['elementCss'].'"; ' . PHP_EOL;
						}
						if ($item['setting_type'] == 1)       {       //for ordinary block
//						$scriptingCode .= 'blockSettingArray[cou1]["setting_type"] = 1; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["element"] = "' . $item['element'] . '"; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["elementPosition"] = ' . $item['elementPosition'] . '; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["elementPlace"] = ' . $item['elementPlace'] . '; ' . PHP_EOL;
						} elseif ($item['setting_type'] == 2) {       //for repeatable
							$scriptingCode .= 'blockSettingArray[cou1]["element"] = "' . $item['element'] . '"; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["elementPosition"] = ' . $item['elementPosition'] . '; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["firstPlace"] = "' . $item['firstPlace'] . '"; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["elementCount"] = "' . $item['elementCount'] . '"; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["elementStep"] = "' . $item['elementStep'] . '"; ' . PHP_EOL;
						} elseif ($item['setting_type'] == 3) {       //for direct block
							$scriptingCode .= 'blockSettingArray[cou1]["element"] = "' . $item['element'] . '"; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["directElement"] = "' . $item['directElement'] . '"; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["elementPosition"] = ' . $item['elementPosition'] . '; ' . PHP_EOL;
							$scriptingCode .= 'blockSettingArray[cou1]["elementPlace"] = ' . $item['elementPlace'] . '; ' . PHP_EOL;
						} elseif (in_array($item['setting_type'],[6,7])) {       //for percentage
							$scriptingCode .= 'blockSettingArray[cou1]["elementPlace"] = ' . $item['elementPlace'] . '; ' . PHP_EOL;
						}
						$scriptingCode .= 'cou1++;'.PHP_EOL;
					}
				}
				$scriptingCode .= 'console.log("bsa-l: "+blockSettingArray.length);' . PHP_EOL;
				$scriptingCode .= '</script>';

				$scriptingCodeResult['before'] = $contentBeforeScript.$cssCode;
				$scriptingCodeResult['after'] = $scriptingCode;
				$scriptingCode = $contentBeforeScript.$cssCode.$scriptingCode;
				/*?><script>console.log('Header addings passed to end');</script><?php*/
				return $scriptingCodeResult;
			} catch (Exception $ex) {
				$messageFLog = 'Some error in creatingJavascriptParserForContent: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return '';
			} catch (Error $er) {
				$messageFLog = 'Some error in creatingJavascriptParserForContent: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return '';
			}
		}
	}
	if (!function_exists('RFWP_WorkProgressLog')) {
	    function RFWP_WorkProgressLog($begin=false, $message='placeholder') {
	        if (!empty($GLOBALS['rb_enableLogs'])) {
		        $currentMtime = [];
		        $currentMtime['time'] = microtime(true);
		        if (!isset($GLOBALS['fWorkProgress'])) {
			        $currentMtime['diffVsFirst'] = 0;
			        $currentMtime['diffVsLast'] = 0;
			        $GLOBALS['fWorkProgress'] = [];
			        $GLOBALS['fWorkProgress']['first'] = $currentMtime['time'];
			        $GLOBALS['fWorkProgress']['last'] = $currentMtime['time'];
		        } else {
			        $currentMtime['diffVsFirst'] = $currentMtime['time'] - $GLOBALS['fWorkProgress']['first'];
			        $currentMtime['diffVsLast'] = $currentMtime['time'] - $GLOBALS['fWorkProgress']['last'];
			        $GLOBALS['fWorkProgress']['last'] = $currentMtime['time'];
		        }

                $messageFWorkProgress = 'work process started;';
                RFWP_Logs::saveLogs(RFWP_Logs::WORK_PROCESS_LOG,
                    PHP_EOL.$message
                    .PHP_EOL.$currentMtime['diffVsFirst']
                    .PHP_EOL.$currentMtime['diffVsLast']);
            }
        }
    }
	if (!function_exists('RFWP_initTestMode')) {
		function RFWP_initTestMode($forced = false) {
            global $rb_testMode;
			if (!isset($rb_testMode) || !empty($forced)) {
                $rb_testMode = !empty(get_option('rb_testMode'));
			}
		}
	}
	if (!function_exists('RFWP_cleanWorkProcessFile')) {
		function RFWP_cleanWorkProcessFile() {
			try {
                RFWP_Logs::clearLog(RFWP_Logs::WORK_PROCESS_LOG);
			}  catch (Exception $ex1) {
				$messageFLog = 'Error in work process log file cleaning: '.$ex1->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			} catch (Error $er1) {
				$messageFLog = 'Error in work process log file cleaning: '.$er1->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			}
		}
	}
	if (!function_exists('RFWP_cronCheckLog')) {
	    function RFWP_cronCheckLog($message='placeholder') {
            if (!empty($GLOBALS['dev_mode'])) {
                RFWP_Logs::saveLogs(RFWP_Logs::CRON_LOG, PHP_EOL . $message);
            }
        }
    }
	if (!function_exists('RFWP_gatherBlocksFromDb')) {
		function RFWP_gatherBlocksFromDb() {
			global $wpdb;
			global $wpPrefix;
			$result = [];
			if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
				RFWP_WorkProgressLog(false,'gatherBlocksFromDb begin');
			}

//			$realbig_settings_info = $wpdb->get_results('SELECT optionName, optionValue FROM '.$GLOBALS['wpPrefix'].'realbig_settings WGPS WHERE optionName IN ("excludedIdAndClasses","blockDuplicate")');
			$excIdClass = null;
			$blockDuplicate = 'yes';
			$adBlocks = [];
			$statusFor404 = 'show';
			$showAdsNoElement = 0;
			$obligatoryMargin = 0;
			$tagsListForTextLength = null;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$realbig_settings_info = $wpdb->get_results($wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT optionName, optionValue FROM `{$wpPrefix}realbig_settings` WGPS WHERE optionName IN (%s, %s, %s, %s,%s,%s)",
                "excludedIdAndClasses", "blockDuplicate", "obligatoryMargin",
                "statusFor404", "showAdsNoElement", "tagsListForTextLength"));
			if (!empty($realbig_settings_info)) {
				foreach ($realbig_settings_info AS $k => $item) {
					if (isset($item->optionValue)) {
						switch ($item->optionName) {
							case 'excludedIdAndClasses':
								$excIdClass = $item->optionValue;
								break;
							case 'obligatoryMargin':
								$obligatoryMargin = $item->optionValue;
								break;
							case 'tagsListForTextLength':
								$tagsListForTextLength = $item->optionValue;
								break;
							case 'blockDuplicate':
								if ($item->optionValue==0) {
									$blockDuplicate = 'no';
								}
								break;
							case 'statusFor404':
								$statusFor404 = $item->optionValue;
								break;
							case 'showAdsNoElement':
								$showAdsNoElement = $item->optionValue;
								break;
						}
					}
				}
				unset($k,$item);
			}
			if ((!is_404())||$statusFor404!='disable') {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$adBlocks = $wpdb->get_results("SELECT * FROM `{$wpPrefix}realbig_plugin_settings` WGPS");
            }

			if (!empty($excIdClass)) {
				$excIdClass .= ';';
            }
			$excIdClass .= ".percentPointerClass;.content_rb;.cnt32_rl_bg_str;.rl_cnt_bg;.addedInserting;#toc_container;table;blockquote";
			if (!empty($excIdClass)) {
				$excIdClass = explode(';', $excIdClass);
				foreach ($excIdClass AS $k1 => $item1) {
					$excIdClass[$k1] = trim($excIdClass[$k1]);
					if (empty($excIdClass[$k1])) {
					    unset($excIdClass[$k1]);
					}
				}
				unset($k1, $item1);
				$excIdClass = implode('","', $excIdClass);
			}
			
			if (!empty($tagsListForTextLength)) {
				$tagsListForTextLength = explode(';', $tagsListForTextLength);

				$array = ["DIV" => ["SECTION"]];
				foreach ($array as $item => $value) {
					if (in_array($item, $tagsListForTextLength)) {
						$tagsListForTextLength = array_merge($tagsListForTextLength, $value);
					}
				}

				foreach ($tagsListForTextLength AS $k1 => $item1) {
					$tagsListForTextLength[$k1] = trim($tagsListForTextLength[$k1]);
					if (empty($tagsListForTextLength[$k1])) {
					    unset($tagsListForTextLength[$k1]);
					}
				}
				unset($k1, $item1);
				$tagsListForTextLength = implode('","', $tagsListForTextLength);
			}

			$result['adBlocks'] = $adBlocks;
			$result['excIdClass'] = $excIdClass;
			$result['blockDuplicate'] = $blockDuplicate;
            $result['obligatoryMargin'] = $obligatoryMargin;
            $result['tagsListForTextLength'] = $tagsListForTextLength;
            $result['showAdsNoElement'] = $showAdsNoElement;
            return $result;
        }
    }
	if (!function_exists('test_sc_oval_exec')) {
		function test_sc_oval_exec() {
			return '<div style="width: 100px; height: 20px; border: 1px solid black; background-color: #0033cc; border-radius: 30%;"></div><script>console.log(\'oval narisoval\');</script>';
//			return '<div style="width: 400px; height: 80px; border: 1px solid black; background-color: #0033cc; border-radius: 30%;"></div><script>console.log(\'oval narisoval\');</script>';
		}
	}
	if (!function_exists('RFWP_checkPageType')) {
		function RFWP_checkPageType() {
			$pasingAllowed = true;
			$arrayOfCheckedTypes = [
				'is_home' => is_home(),
				'is_front_page' => is_front_page(),
				'is_page' => is_page(),
				'is_single' => is_single(),
				'is_singular' => is_singular(),
				'is_archive' => is_archive(),
				'is_category' => is_category(),
			];
			try {
				if ((!empty($arrayOfCheckedTypes['is_home'])||!empty($arrayOfCheckedTypes['is_front_page']))&&!empty($GLOBALS['pageChecks']['excludedMainPage'])) {
					$pasingAllowed = false;
				} elseif (in_array(true, $arrayOfCheckedTypes)) {
					if (!empty($GLOBALS['pageChecks']['excludedPageTypes'])) {
						$excludedPageTypesString = $GLOBALS['pageChecks']['excludedPageTypes'];
						if ($excludedPageTypesString!='nun') {
							$excludedPageTypes = explode(',', $excludedPageTypesString);
							foreach ($excludedPageTypes AS $k => $item) {
								if (!empty($arrayOfCheckedTypes[$item])) {
									$pasingAllowed = false;
									break;
								}
							}
						}
					}
				} else {
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'adBlocksToContentInsertingFunction forbidden page type end');
					}
				}
			} catch (Exception $ex) {
				$messageFLog = 'Some error in RFWP_launch_without_content_function: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			} catch (Error $er) {
				$messageFLog = 'Some error in RFWP_launch_without_content_function: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			}
			return $pasingAllowed;
		}
	}
	if (!function_exists('RFWP_getTablePrefix')) {
	    function RFWP_getTablePrefix() {
		    if (!empty($GLOBALS['wpPrefix'])) {
			    $wpPrefix = $GLOBALS['wpPrefix'];
		    } else {
			    global $table_prefix;
			    $wpPrefix = $table_prefix;
		    }

		    return $wpPrefix;
        }
    }
	if (!function_exists('RFWP_getTagsCategories')) {
	    function RFWP_getTagsCategories() {
	        global $rb_tagsCategories;
	        $args = ['hide_empty'=>false];
	        $rb_tagsCategoriesFinal = [];

	        $taxonomies = RFWP_getTaxonomies(true);
	        $usedTaxonomies = RFWP_getUsedTaxonomiesFromDB();

	        foreach ($taxonomies as $type => $items) {
	            if (!empty($items)) {
		            if (!isset($usedTaxonomies[$type])) {
			            $usedTaxonomies[$type] = [];
		            }

		            $taxonomy = array_keys($items)[0];
		            array_unshift($usedTaxonomies[$type], $taxonomy);
	            }
	        }
	        unset($taxonomies);

		    if (empty($rb_tagsCategories)) {
			    $rb_tagsCategories = [];
            }

		    if (empty($rb_tagsCategories['categories'])) {
		        if (!empty($usedTaxonomies['categories'])) {
			        $args['taxonomy'] = $usedTaxonomies['categories'];
		        }

		        $rb_tagsCategories['categories'] = get_categories($args);
		        $rb_tagsCategoriesFinal['categories'] = [];
		        if (!empty($rb_tagsCategories['categories'])) {
		            foreach ($rb_tagsCategories['categories'] AS $item) {
//			            $rb_tagsCategoriesFinal['categories'][$item->slug] = $item->name;
			            $rb_tagsCategoriesFinal['categories'][$item->term_id] = $item->name;
                    }
		            unset($item);
                } else {
			        $rb_tagsCategoriesFinal['categories'] = '_nun_';
                }
            }

	        if (empty($rb_tagsCategories['tags'])) {
		        if (!empty($usedTaxonomies['tags'])) {
			        $args['taxonomy'] = $usedTaxonomies['tags'];
		        }

		        $rb_tagsCategories['tags'] = get_tags($args);
		        $rb_tagsCategoriesFinal['tags'] = [];
		        if (!empty($rb_tagsCategories['tags'])) {
			        foreach ($rb_tagsCategories['tags'] AS $item) {
//				        $rb_tagsCategoriesFinal['tags'][$item->slug] = $item->name;
				        $rb_tagsCategoriesFinal['tags'][$item->term_id] = $item->name;
			        }
			        unset($item);
		        }
	        }

	        if (!empty($rb_tagsCategoriesFinal)) {
		        $rb_tagsCategories = $rb_tagsCategoriesFinal;
            }

	        $GLOBALS['rb_tagsCategories'] = $rb_tagsCategories;
	        return $rb_tagsCategories;
        }
    }
	if (!function_exists('RFWP_getTaxonomies')) {
	    function RFWP_getTaxonomies($all = false) {
	        global $rb_taxonomies;

	        if (empty($rb_taxonomies))
	        {
		        $args = [
			        'show_ui' => true,
		        ];

		        $types = [
			        'categories' => 'post_categories_meta_box',
			        'tags' => 'post_tags_meta_box'
		        ];

		        foreach ($types as $type => $metaBox)
		        {
			        $args['meta_box_cb'] = $metaBox;

			        if (empty($rb_taxonomies[$type])) {
				        $rb_taxonomies[$type] = [];
			        }

			        $taxonomies = get_taxonomies($args, 'objects');

			        foreach ($taxonomies as $taxonomy) {
				        $rb_taxonomies[$type][$taxonomy->name] = $taxonomy->label;
			        }
		        }
	        }

	        $taxonomies = $rb_taxonomies;

		    if (!$all) {
		        foreach ($taxonomies as &$taxonomy) {
			        array_shift($taxonomy);
		        }
		    }

	        return $taxonomies;
        }
    }
	if (!function_exists('RFWP_getUsedTaxonomiesFromDB')) {
	    function RFWP_getUsedTaxonomiesFromDB() {
	        global $usedTaxonomies;

	        if (empty($usedTaxonomies)) {
		        global $wpdb;
		        global $wpPrefix;

		        $usedTaxonomies = [];
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
		        $array = $wpdb->get_results($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` WGPS WHERE optionName = %s",
                    "usedTaxonomies"));

		        if (!empty($array[0]->optionValue)) {
			        $usedTaxonomies = json_decode($array[0]->optionValue, true);
		        }

	        }

		    return $usedTaxonomies;
	    }
	}
	if (!function_exists('RFWP_addWebnavozJs')) {
		function RFWP_addWebnavozJs() {
		    $plugin1 = 'webnavoz-likes/webnavoz-likes.php';
			if (is_plugin_active($plugin1)) {
				?><script><?php include_once (plugin_dir_path(__FILE__).'assets/js/webnawozComp.js'); ?></script><?php
			}
		}
	}
	if (!function_exists('RFWP_addContentContainer')) {
		function RFWP_addContentContainer() {
		    $gatherContentTimeoutLong = get_transient(RFWP_Variables::GATHER_CONTENT_LONG);
		    $gatherContentTimeoutShort = get_transient(RFWP_Variables::GATHER_CONTENT_SHORT);
		    if (empty($gatherContentTimeoutLong)&&empty($gatherContentTimeoutShort)) {
//		        require_once (ABSPATH."/wp-admin/includes/plugin.php");
//		        set_transient('gatherContentContainerShort', true, 60);
//			    add_action('wp_ajax_RFWP_saveContentContainer', 'RFWP_saveContentContainer');
//			    add_action('wp_ajax_nopriv_RFWP_saveContentContainer', 'RFWP_saveContentContainer');
//			    add_action('wp_ajax_test123', 'test123');
//			    add_action('wp_ajax_nopriv_test123', 'test123');
		    }
		}
	}
	if (!function_exists('RFWP_saveContentContainer')) {
		function RFWP_saveContentContainer() {
			$result = [];
			$result['error'] = '';
			$result['status'] = 'error';

			$gatherContentTimeoutLong = get_transient(RFWP_Variables::GATHER_CONTENT_LONG);
			$gatherContentTimeoutShort = get_transient(RFWP_Variables::GATHER_CONTENT_SHORT);

			if (empty($gatherContentTimeoutLong) && empty($gatherContentTimeoutShort)
                && !empty($_POST["_csrf"]) && wp_verify_nonce($_POST["_csrf"], RFWP_Variables::CSRF_USER_JS_ACTION)) {
				$data = $_POST['data'];

				if (!empty($data)) {
                    $saveResult = RFWP_Utils::saveToRbSettings(sanitize_text_field($data), 'contentSelector');
					if (!empty($saveResult)) {
						$result['status'] = 'success';
					} else {
						$result['error'] = 'save error';
					}
				}
			} else {
				$result['status'] = 'already saved';
			}

			return $result;
		}
	}
    if (!function_exists('RFWP_getJsToHead')) {
        function RFWP_getJsToHead() {
            $jsToHead = null;
            try {
                global $wpdb;

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $jsToHead = $wpdb->get_var($wpdb->prepare("SELECT optionValue FROM `{$GLOBALS['wpPrefix']}realbig_settings` " .
                    "WHERE optionName = %s", "jsToHead"));
                if ($jsToHead!==null) {
                    $jsToHead = intval($jsToHead);
                }
            }
            catch (Exception $ex) {
                $errorText = __FUNCTION__." error: ".$ex->getMessage();
                RBAG_Logs::saveLogs('errorsLog', $errorText);
            }
            catch (Error $ex) {
                $errorText = __FUNCTION__." error: ".$ex->getMessage();
                RBAG_Logs::saveLogs('errorsLog', $errorText);
            }

            return $jsToHead;
        }
    }
    if (!function_exists('RFWP_getPluginSetting')) {
        function RFWP_getPluginSetting($settingName, $getFromGlobal = true, $addToGlobal = false) {
            $result = null;
            try {
                if (!empty($getFromGlobal)&&isset($GLOBALS['rb_variables'][$settingName])) {
	                $result = $GLOBALS['rb_variables'][$settingName];
                } else {
	                global $wpdb;
	                $wpPrefix = RFWP_getTablePrefix();

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	                $result = $wpdb->prepare($wpdb->get_var("SELECT optionValue FROM `{$wpPrefix}realbig_settings` " .
                        "WHERE optionName = %s"), $settingName);
	                if (!empty($addToGlobal)) {
		                $GLOBALS['rb_variables'][$settingName] = $result;
	                }
                }
            }
            catch (Exception $ex) {
                $errorText = __FUNCTION__." error: ".$ex->getMessage();
                RBAG_Logs::saveLogs('errorsLog', $errorText);
            }
            catch (Error $ex) {
                $errorText = __FUNCTION__." error: ".$ex->getMessage();
                RBAG_Logs::saveLogs('errorsLog', $errorText);
            }

            return $result;
        }
    }
	if (!function_exists('RFWP_launch_cache')) {
		function RFWP_launch_cache($getRotator, $getDomain) {
			?><script>
                function onErrorPlacing() {
                    if (typeof cachePlacing !== 'undefined' && typeof cachePlacing === 'function' && typeof window.jsInputerLaunch !== 'undefined' && [15, 10].includes(window.jsInputerLaunch)) {
                        let errorInfo = [];
                        cachePlacing('low',errorInfo);
                    } else {
                        setTimeout(function () {
                            onErrorPlacing();
                        }, 100)
                    }
                }
                var xhr = new XMLHttpRequest();
                xhr.open('GET',"<?php echo esc_url("//$getDomain/$getRotator.min.js") ?>",true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.status != 200) {
                        if (xhr.statusText != 'abort') {
                            onErrorPlacing();
                        }
                    }
                };
                xhr.send();
            </script><?php
		}
	}
	if (!function_exists('RFWP_launch_cache_local')) {
		function RFWP_launch_cache_local($getRotator, $getDomain) {
			?><script>
                function onErrorPlacing() {
                    if (typeof cachePlacing !== 'undefined' && typeof cachePlacing === 'function' && typeof window.jsInputerLaunch !== 'undefined' && [15, 10].includes(window.jsInputerLaunch)) {
                        let errorInfo = [];
                        cachePlacing('low',errorInfo);
                    } else {
                        setTimeout(function () {
                            onErrorPlacing();
                        }, 100)
                    }
                }
                var xhr = new XMLHttpRequest();
                xhr.open('GET',"<?php echo esc_url("//$getDomain/$getRotator.json") ?>",true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.status != 200) {
                        if (xhr.statusText != 'abort') {
                            onErrorPlacing();
                        }
                    }
                };
                xhr.send();
            </script><?php
		}
	}
}
catch (Exception $ex)
{
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

        RFWP_Utils::saveToRbSettings('textEdit: ' . $ex->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($ex); ?></div><?php
}
catch (Error $er)
{
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

        RFWP_Utils::saveToRbSettings('textEdit: ' . $er->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($er); ?></div><?php
}