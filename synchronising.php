<?php

if (!defined("ABSPATH")) { exit;}

try {
	if (!function_exists('RFWP_synchronize')) {
		function RFWP_synchronize($tokenInput, $sameTokenResult, $requestType, $updateLogs) {
			global $wpdb;
			global $shortcode_tags;
			$wpPrefix = RFWP_getWpPrefix();
			$shortcodesToSend = array_keys($shortcode_tags);
			$menuItemList = RFWP_getMenuList();
			$permalinkStatus = RFWP_checkPermalink();
            $pluginVersion = RFWP_Utils::getVersion();
			$rssSelectiveOffField = RFWP_rssSelectiveOffFieldGet();
            $unsuccessfullAjaxSyncAttempt = 0;

			if (!empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON))
                && empty(apply_filters('wp_doing_ajax', defined('DOING_AJAX') && DOING_AJAX))) {
				RFWP_cronCheckLog('cron in sync passed');
			}

//			RFWP_checkModules();

			if (!empty($_SERVER['HTTP_HOST'])) {
				$urlData = $_SERVER['HTTP_HOST'];
			} elseif (!empty($_SERVER['SERVER_NAME'])) {
				$urlData = $_SERVER['SERVER_NAME'];
			} else {
				$urlData = 'empty';
            }

			$otherInfo = RFWP_otherInfoGather();

			$getCategoriesTags = RFWP_getTagsCategories();
			if (!empty($getCategoriesTags)) {
				$getCategoriesTags = wp_json_encode($getCategoriesTags);
			}

			try {
    			$url = 'https://' . RFWP_getSyncDomain() . '/api/wp-get-settings';

                /** for WP request **/
				$dataForSending = [
				    'body'  => [
                        'token'     => $tokenInput,
                        'sameToken' => $sameTokenResult,
                        'urlData'   => $urlData,
                        'getCategoriesTags' => $getCategoriesTags,
                        'getShortcodes' => wp_json_encode($shortcodesToSend),
                        'getMenuList' => wp_json_encode($menuItemList),
                        'otherInfo' => $otherInfo,
                        'pluginVersion' => $pluginVersion,
                        'rssSelectiveOffField' => $rssSelectiveOffField,
                        'enableLogs' => !empty($updateLogs) ? RFWP_Utils::getFromRbSettings('enableLogs') : null,
                    ],
				    'sslverify' => false,
                    'timeout' => 30
				];
				try {
					$jsonToken = wp_remote_post($url, $dataForSending);
					if (!is_wp_error($jsonToken)) {
						$jsonToken = $jsonToken['body'];
						if (!empty($jsonToken)) {
							$GLOBALS['connection_request_rezult']   = 1;
							$GLOBALS['connection_request_rezult_1'] = 'success';
						}
                    } else {
						$error                                  = $jsonToken->get_error_message();
						$GLOBALS['connection_request_rezult']   = 'Connection error: ' . $error;
						$GLOBALS['connection_request_rezult_1'] = 'Connection error: ' . $error;
						$unsuccessfullAjaxSyncAttempt           = 1;
						$messageFLog = 'Synchronisation request error: '.$error.';';
                        RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					}
				}
				catch (Exception $e) {
					$GLOBALS['tokenStatusMessage'] = $e['message'];
					if ( $requestType == 'ajax' ) {
						$ajaxResult = $e['message'];
					}
					$unsuccessfullAjaxSyncAttempt = 1;
					$messageFLog = 'Synchronisation request system error: '.$e['message'].';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				}
				catch (Error $e) {
					$GLOBALS['tokenStatusMessage'] = $e['message'];
					if ( $requestType == 'ajax' ) {
						$ajaxResult = $e['message'];
					}
					$unsuccessfullAjaxSyncAttempt = 1;
					$messageFLog = 'Synchronisation request system error: '.$e['message'].';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				}
				if (!empty($jsonToken)&&!is_wp_error($jsonToken)) {
					$decodedToken                  = json_decode($jsonToken, true);
					$GLOBALS['tokenStatusMessage'] = $decodedToken['message'];
					if ($requestType == 'ajax') {
						$ajaxResult = $decodedToken['message'];
					}
					if (!empty($decodedToken)) {
					    $sanitisedStatus = sanitize_text_field($decodedToken['status']);
					    if ($sanitisedStatus=='success'||$sanitisedStatus=='empty_success') {
						    try {
							    if (!empty($decodedToken['data'])) {
								    if (!empty($decodedToken['excludedPages'])) {
								        $sanitisedExcludedPages = sanitize_text_field($decodedToken['excludedPages']);
                                    } else {
									    $sanitisedExcludedPages = '';
                                    }
                                    RFWP_Utils::saveToRbSettings($sanitisedExcludedPages, 'excludedPages');

                                    if (isset($decodedToken['excludedMainPage'])) {
                                        if (!empty($decodedToken['excludedMainPage'])) {
                                            $sanitisedExcludedMainPages = sanitize_text_field($decodedToken['excludedMainPage']);
                                            if (intval($sanitisedExcludedMainPages)) {
                                                if (strlen($sanitisedExcludedMainPages) > 1) {
                                                    $sanitisedExcludedMainPages = '';
                                                }
                                            } else {
                                                $sanitisedExcludedMainPages = '';
                                            }
                                        } else {
                                            $sanitisedExcludedMainPages = '';
                                        }

                                        RFWP_Utils::saveToRbSettings($sanitisedExcludedMainPages, 'excludedMainPage');
                                    }

								    $counter = 0;
                                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
								    $wpdb->query("DELETE FROM `{$wpPrefix}realbig_plugin_settings`");
                                    $params = [];
								    $sqlTokenSave = "INSERT INTO `{$wpPrefix}realbig_plugin_settings` (text, block_number, setting_type, element, directElement, elementPosition, " .
                                        "elementPlace, firstPlace, elementCount, elementStep, minSymbols, maxSymbols, minHeaders, maxHeaders, " .
                                        "onCategories, offCategories, onTags, offTags, elementCss, showNoElement) VALUES ";
								    foreach ($decodedToken['data'] AS $k => $item) {
									    $counter ++;
									    $sqlTokenSave .= ($counter != 1 ?", ":"") .
                                            "(%s, %d, %d, %s, %s, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s, %s, %s";
                                        array_push($params, $item['text'], (int) sanitize_text_field($item['block_number']),
                                            (int) sanitize_text_field($item['setting_type']), sanitize_text_field($item['element']),
                                            sanitize_text_field($item['directElement']), (int) sanitize_text_field($item['elementPosition']),
                                            (int) sanitize_text_field($item['elementPlace']), (int) sanitize_text_field($item['firstPlace']),
                                            (int) sanitize_text_field($item['elementCount']), (int) sanitize_text_field($item['elementStep']),
                                            (int) sanitize_text_field($item['minSymbols']), (int) sanitize_text_field($item['maxSymbols']),
                                            (int) sanitize_text_field($item['minHeaders']), (int) sanitize_text_field($item['maxHeaders']),
                                            sanitize_text_field($item['onCategories']), sanitize_text_field($item['offCategories']),
                                            sanitize_text_field($item['onTags']), sanitize_text_field($item['offTags']),
                                            sanitize_text_field($item['elementCss']));

                                        if (sanitize_text_field($item['showNoElement']) != "") {
                                            $sqlTokenSave .= ", %d";
                                            array_push($params, (int) sanitize_text_field($item['showNoElement']));
                                        } else {
                                            $sqlTokenSave .= ", null";
                                        }
                                        $sqlTokenSave .= ")";
								    }
								    unset($k, $item);
								    $sqlTokenSave .= " ON DUPLICATE KEY UPDATE text = values(text), setting_type = values(setting_type), " .
                                        "element = values(element), directElement = values(directElement), elementPosition = values(elementPosition), " .
                                        "elementPlace = values(elementPlace), firstPlace = values(firstPlace), elementCount = values(elementCount), " .
                                        "elementStep = values(elementStep), minSymbols = values(minSymbols), maxSymbols = values(maxSymbols), " .
                                        "minHeaders = values(minHeaders), maxHeaders = values(maxHeaders), onCategories = values(onCategories), " .
                                        "offCategories = values(offCategories), onTags = values(onTags), offTags = values(offTags), " .
                                        "elementCss = values(elementCss), showNoElement = values(showNoElement) ";
                                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
								    $wpdb->query($wpdb->prepare($sqlTokenSave, $params));
							    } elseif (empty($decodedToken['data'])&&sanitize_text_field($decodedToken['status']) == "empty_success") {
                                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
								    $wpdb->query("DELETE FROM `{$wpPrefix}realbig_plugin_settings`");
							    }

							    // if no needly note, then create
                                RFWP_Utils::saveToRbSettings($tokenInput, '_wpRealbigPluginToken');

							    if (!empty($decodedToken['dataUniversalPush'])) {
							        $sanitisedPushUniversalStatus = sanitize_text_field($decodedToken['dataUniversalPush']['pushStatus']);
							        $sanitisedPushUniversalData = sanitize_text_field($decodedToken['dataUniversalPush']['pushCode']);
							        $sanitisedPushUniversalDomain = sanitize_text_field($decodedToken['dataUniversalPush']['pushDomain']);
								    RFWP_Utils::saveToRbSettings($sanitisedPushUniversalStatus, 'pushUniversalStatus');
                                    RFWP_Utils::saveToRbSettings($sanitisedPushUniversalData, 'pushUniversalCode');
                                    RFWP_Utils::saveToRbSettings($sanitisedPushUniversalDomain, 'pushUniversalDomain');
							    }
							    if (!empty($decodedToken['periodSync'])) {
							        $sanitised = sanitize_text_field($decodedToken['periodSync']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'periodSync');
							    }
							    if (!empty($decodedToken['domain'])) {
							        $sanitised = sanitize_text_field($decodedToken['domain']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'domain');
							    }
							    if (!empty($decodedToken['rotator'])) {
							        $sanitised = sanitize_text_field($decodedToken['rotator']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'rotator');
							    }
							    if (!empty($decodedToken['rotatorCode'])) {
                                    $sanitised = sanitize_text_field($decodedToken['rotatorCode']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'rotatorCode');
							    }
							    if (isset($decodedToken['adWithStatic'])) {
                                    $sanitised = sanitize_text_field($decodedToken['adWithStatic']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'adWithStatic');
							    }
							    if (isset($decodedToken['showAdsNoElement'])) {
                                    $sanitised = sanitize_text_field($decodedToken['showAdsNoElement']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'showAdsNoElement');
							    }
							    /** Selected taxonomies */
							    if (isset($decodedToken['taxonomies'])) {
                                    $sanitised = sanitize_text_field(wp_json_encode($decodedToken['taxonomies'], JSON_UNESCAPED_UNICODE));
                                    RFWP_Utils::saveToRbSettings($sanitised, 'usedTaxonomies');
							    }
							    /** End of selected taxonomies */
							    /** Excluded page types */
							    if (isset($decodedToken['excludedPageTypes'])) {
                                    $sanitised = sanitize_text_field($decodedToken['excludedPageTypes']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'excludedPageTypes');
							    }
							    /** End of excluded page types */
							    /** Excluded id and classes */
							    if (isset($decodedToken['excludedIdAndClasses'])) {
                                    $sanitised = sanitize_text_field($decodedToken['excludedIdAndClasses']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'excludedIdAndClasses');
							    }
							    /** End of excluded id and classes */
							    /** Blocks duplicate denying option */
							    if (isset($decodedToken['blockDuplicate'])) {
                                    $sanitised = sanitize_text_field($decodedToken['blockDuplicate']);
                                    RFWP_Utils::saveToRbSettings($sanitised, 'blockDuplicate');
							    }
							    /** End of blocks duplicate denying option */
							    /** Create it for compatibility with some plugins */
							    if (empty($GLOBALS['wp_rewrite'])) {
								    $GLOBALS['wp_rewrite'] = new WP_Rewrite();
							    }
//							    $GLOBALS['wp_rewrite']->flush_rules(false);
							    /** End of creating of that for compatibility with some plugins */
							    /** Insertings */
							    if (!empty($decodedToken['insertings'])) {
								    $insertings = $decodedToken['insertings'];
                                    $oldInserts = get_posts(['post_type' => 'rb_inserting','numberposts' => 100]);
                                    if (!empty($oldInserts)&&in_array($insertings['status'],['ok','empty'])) {
	                                    foreach ($oldInserts AS $k => $item) {
		                                    wp_delete_post($item->ID);
                                        }
	                                    unset($k, $item);
                                    }

							        if ($insertings['status']='ok') {
							            foreach ($insertings['data'] AS $k=>$item) {
							                $content_for_post = 'begin_of_header_code' . $item['headerField'] .
                                                'end_of_header_code&begin_of_body_code' . $item['bodyField'] . 'end_of_body_code';

								            $postarr = [
									            'post_content' => $content_for_post,
									            'post_title'   => $item['position_element'],
									            'post_excerpt' => $item['position'],
									            'post_name'    => $item['name'],
									            'post_status'  => "publish",
									            'post_type'    => 'rb_inserting',
									            'post_author'  => 0,
									            'pinged'       => $item['limitationUse'],
								            ];
								            require_once(ABSPATH."/wp-includes/pluggable.php");
								            if (empty($GLOBALS['wp_rewrite'])) {
									            $GLOBALS['wp_rewrite'] = new WP_Rewrite();
                                            }
								            $saveInsertResult = wp_insert_post($postarr, true);
                                        }
								        unset($k, $item);
							        }
                                }
							    /** End of insertings */
							    /** Shortcodes */
							    $oldShortcodes = get_posts(['post_type' => 'rb_shortcodes','numberposts' => 100]);
							    if (!empty($oldShortcodes)) {
								    foreach ($oldShortcodes AS $k => $item) {
									    wp_delete_post($item->ID);
								    }
								    unset($k, $item);
							    }
							    if (!empty($decodedToken['shortcodes'])) {
							        $shortcodes = $decodedToken['shortcodes'];

                                    foreach ($shortcodes AS $k=>$item) {
								        if (!empty($item)) {
									        $postarr = [
										        'post_content' => $item['code'],
										        'post_title'   => $item['id'],
										        'post_excerpt' => $item['blockId'],
										        'post_name'    => 'shortcode',
										        'post_status'  => "publish",
										        'post_type'    => 'rb_shortcodes',
										        'post_author'  => 0,
									        ];
									        require_once(ABSPATH."/wp-includes/pluggable.php");
//                                            remove_all_filters("pre_post_content");
                                            remove_all_filters("content_save_pre");
									        $saveInsertResult = wp_insert_post($postarr, true);
                                        }
                                    }
                                    unset($k, $item);
                                }
							    /** End of shortcodes */
                                /** Turbo rss */
                                if (!empty($decodedToken['turboSettings'])) {
                                    if (!empty($rssSelectiveOffField['delete'])||!empty($rssSelectiveOffField['restore'])) {
	                                    $rssSelectiveOffField = RFWP_rssSelectiveOffFieldToArray($rssSelectiveOffField);
                                        $rbTurboSettings = $decodedToken['turboSettings'];
                                        if (is_string($rbTurboSettings)) {
	                                        $rbTurboSettings = json_decode($rbTurboSettings, true);
                                        }
                                        if (!empty($rbTurboSettings)&&is_array($rbTurboSettings)&&!empty($rbTurboSettings['feedSelectiveOffField'])) {
                                            $feedSelectiveOffField = $rbTurboSettings['feedSelectiveOffField'];
                                            if (is_string($feedSelectiveOffField)) {
	                                            $feedSelectiveOffField = explode("\n", str_replace(array("\r\n", "\r"),
                                                    "\n", $feedSelectiveOffField));
                                            }
	                                        $newRssSelectiveOffField = $rssSelectiveOffField;
	                                        foreach ($rssSelectiveOffField as $k3 => $item3) {
		                                        foreach ($item3 as $k2 => $item2) {
			                                        if (in_array($item2, $feedSelectiveOffField)) {
			                                            if ($k3=='delete') {
				                                            unset($newRssSelectiveOffField[$k3][$k2]);
                                                        }
			                                        } else {
				                                        if ($k3=='restore') {
					                                        unset($newRssSelectiveOffField[$k3][$k2]);
                                                        }
                                                    }
		                                        }
		                                        unset($k2, $item2);

		                                        RFWP_rssSelectiveOffFieldOptionSave($newRssSelectiveOffField[$k3], $k3);
                                            }
	                                        unset($k3, $item3);
                                        } else {
	                                        update_option('rfwp_selectiveOffFieldRestore', '');
                                        }
                                    }
                                    $turboSettings = wp_json_encode($decodedToken['turboSettings'], JSON_UNESCAPED_UNICODE);
                                    update_option('rb_TurboRssOptions', $turboSettings, false);
                                } elseif (isset($decodedToken['turboSettings'])) {
	                                update_option('rb_TurboRssOptions', '[]', false);
                                }
                                /** End of Turbo rss */
							    /** Turbo rss ads */
                                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
							    $wpdb->query("DELETE FROM `{$wpPrefix}realbig_turbo_ads`");
							    if (!empty($decodedToken['turboAdSettings'])) {
								    $counter = 0;
                                    $params = [];
								    $sqlTokenSave = "INSERT INTO `{$wpPrefix}realbig_turbo_ads` (blockId, adNetwork, adNetworkYandex, adNetworkAdfox, settingType, element, " .
                                        "elementPosition, elementPlace) VALUES ";
								    unset($k, $item);
								    foreach ($decodedToken['turboAdSettings'] AS $k => $item) {
									    $counter ++;
									    $sqlTokenSave .= ($counter != 1 ?", ":"") . "(%d, %s, %s, %s, %s, %s, %d, %d)";
                                        array_push($params,(int) sanitize_text_field($item['blockId']), sanitize_text_field($item['adNetwork']),
                                            sanitize_text_field($item['adNetworkYandex']), $item['adNetworkAdfox'],
                                            sanitize_text_field($item['settingType']), sanitize_text_field($item['element']),
                                            (int) sanitize_text_field($item['elementPosition']), (int) sanitize_text_field($item['elementPlace']));
								    }
								    unset($k, $item, $counter);
								    $sqlTokenSave .= " ON DUPLICATE KEY UPDATE blockId = values(blockId), adNetwork = values(adNetwork), " .
                                        "adNetworkYandex = values(adNetworkYandex), adNetworkAdfox = values(adNetworkAdfox), " .
                                        "settingType = values(settingType), element = values(element), elementPosition = values(elementPosition), " .
                                        "elementPlace = values(elementPlace) ";
                                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
								    $wpdb->query($wpdb->prepare($sqlTokenSave, $params));
							    }
							    /** End of Turbo rss ads */
							    /** Amp */
                                if (!empty($decodedToken['ampSettings'])) {
	                                $turboSettings = wp_json_encode($decodedToken['ampSettings'], JSON_UNESCAPED_UNICODE);
	                                update_option('rb_ampSettings', $turboSettings, false);
                                }
							    /** End of Amp */
                                /** Amp ads */
                                if (!empty($decodedToken['ampAdSettings'])) {
                                    $counter = 0;
                                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
                                    $wpdb->query("DELETE FROM `{$wpPrefix}realbig_amp_ads`");
                                    $params = [];
                                    $sqlTokenSave = "INSERT INTO `{$wpPrefix}realbig_amp_ads` (blockId, adField, settingType, element, elementPosition, elementPlace) VALUES ";
                                    foreach ($decodedToken['ampAdSettings'] AS $k => $item) {
                                        $counter ++;
                                        $sqlTokenSave .= ($counter != 1 ?", ":"") . "(%d, %s, %s, %s, %d, %d)";
                                        array_push($params, (int) sanitize_text_field($item['blockId']), sanitize_text_field($item['adField']),
                                            sanitize_text_field($item['settingType']), sanitize_text_field($item['element']),
                                            (int) sanitize_text_field($item['elementPosition']), (int) sanitize_text_field($item['elementPlace']));
                                    }
                                    unset($k, $item, $counter);
                                    $sqlTokenSave .= " ON DUPLICATE KEY UPDATE blockId = values(blockId), adField = values(adField), " .
                                        "settingType = values(settingType), element = values(element), elementPosition = values(elementPosition), " .
                                        "elementPlace = values(elementPlace) ";
                                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
                                    $wpdb->query($wpdb->prepare($sqlTokenSave, $params));
                                }
                                /** End of Amp ads */
                                /** 404 pages status */
							    if (!empty($decodedToken['statusFor404'])) {
								    $statusFor404 = sanitize_text_field($decodedToken['statusFor404']);
                                    RFWP_Utils::saveToRbSettings($statusFor404, 'statusFor404');
                                }
                                /** End of 404 pages status */
                                /** Test Mode */
							    if (isset($decodedToken['testMode'])) {
								    $testMode = intval($decodedToken['testMode']);
								    $oldTestOption = get_option('rb_testMode');
								    update_option('rb_testMode', $testMode, false);
								    RFWP_initTestMode(true);
								    if (!empty($oldTestOption)&&empty($testMode)) {
									    RFWP_cleanWorkProcessFile();
                                    }
							    }
							    /** End of Test Mode */
                                if (isset($decodedToken['jsToHead'])) {
                                    $jsToHead = sanitize_text_field($decodedToken['jsToHead']);
                                    RFWP_Utils::saveToRbSettings($jsToHead, 'jsToHead');
                                }
                                if (isset($decodedToken['obligatoryMargin'])) {
                                    $obligatoryMargin = sanitize_text_field($decodedToken['obligatoryMargin']);
                                    RFWP_Utils::saveToRbSettings($obligatoryMargin, 'obligatoryMargin');
                                }
                                if (isset($decodedToken['enableLogs'])) {
                                    $obligatoryMargin = sanitize_text_field($decodedToken['enableLogs']);
                                    RFWP_Utils::saveToRbSettings($obligatoryMargin, 'enableLogs');
                                }
                                if (isset($decodedToken['tagsListForTextLength'])) {
                                    $tagsListForTextLength = sanitize_text_field($decodedToken['tagsListForTextLength']);
                                    RFWP_Utils::saveToRbSettings($tagsListForTextLength, 'tagsListForTextLength');
                                }

							    $GLOBALS['token'] = $tokenInput;

							    wp_cache_flush();
							    if (class_exists('RFWP_CachePlugins') && !empty($_POST) &&
                                    !empty($_POST["_csrf"]) && wp_verify_nonce($_POST["_csrf"], RFWP_Variables::CSRF_ACTION) &&
                                    !empty($_POST['cache_clear']) && $_POST['cache_clear']=='on') {
                                    RFWP_CachePlugins::cacheClear();
                                }

							    RFWP_Cache::deleteDeviceCaches();
						    } catch ( Exception $e ) {
							    $GLOBALS['tokenStatusMessage'] = $e->getMessage();
							    $unsuccessfullAjaxSyncAttempt  = 1;
							    $messageFLog = 'Some error in synchronize: '.$e->getMessage().';';
                                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
						    }
                        }
					}
				} else {
					$decodedToken                  = null;
					$GLOBALS['tokenStatusMessage'] = 'ошибка соединения';
					$decodedToken['status']        = 'error';
					if ($requestType == 'ajax') {
						$ajaxResult = 'connection error';
					}
					$unsuccessfullAjaxSyncAttempt = 1;
					$messageFLog = 'Connection error;';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				}

                RFWP_Utils::saveToRbSettings('success', 'successUpdateMark');

				try {
					RFWP_Cache::deleteAttemptCache();
					RFWP_Cache::setAttemptCache();

					if ($decodedToken['status'] == 'success') {
                        $time = time();
                        RFWP_Utils::saveToRbSettings($time, 'token_sync_time');
                        $GLOBALS['tokenTimeUpdate'] = $time;
                        $GLOBALS['statusColor']     = 'green';
					}

                    $schedule = wp_get_scheduled_event('rb_cron_hook');
                    $interval = RFWP_getPeriodSync();

                    if (!empty($schedule) && $schedule->timestamp > time() && !empty($schedule->interval)
                            && $schedule->interval != $interval) {
                        RFWP_cronAutoGatheringLaunch();
                    }
				} catch (Exception $e) {
//					echo $e->getMessage();
					$messageFLog = 'Some error in synchronize: '.$e->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				}
				if ($requestType == 'ajax') {
					if (empty($ajaxResult)) {
						$messageFLog = 'Ajax result error;';
                        RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
						return 'error';
					} else {
						return $ajaxResult;
					}
				} else {
					wp_cache_flush();
                }
                RFWP_Cache::deleteProcessCache();
			}
			catch (Exception $e) {
				$messageFLog = 'Some error in synchronize: '.$e->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

				if ($requestType == 'ajax') {
					if (empty($ajaxResult)) {
						return 'error';
					} else {
						return $ajaxResult;
					}
				}
			}
			catch (Error $e) {
				$messageFLog = 'Some error in synchronize: '.$e->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

				if ($requestType == 'ajax') {
					if (empty($ajaxResult)) {
						return 'error';
					} else {
						return $ajaxResult;
					}
				}
			}
			return false;
		}
	}
	if (empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON))) {
		if (!function_exists('RFWP_savingCodeForCache')) {
			function RFWP_savingCodeForCache($blocksAd=null) {
				global $wpdb;
				global $wpPrefix;
				$resultTypes = [];

				try {
                    $url = 'https://' . RFWP_getSyncDomain() . '/api/wp-get-ads';

					$dataForSending = [
						'body'  => [
							'blocksAd' => $blocksAd
						],
						'sslverify' => false,
					];

	            $jsonResult = wp_remote_post($url, $dataForSending);

					if (!empty($jsonResult)&&!is_wp_error($jsonResult)) {
						$decodedResult = json_decode($jsonResult['body'], true);
						if (!empty($decodedResult)) {
							$sanitisedStatus = sanitize_text_field($decodedResult['status']);
							if ($sanitisedStatus=='success') {
								$resultData = $decodedResult['data'];

								$resultTypes['mobile'] = false;
								$resultTypes['tablet'] = false;
								$resultTypes['desktop'] = false;

								require_once(ABSPATH."/wp-includes/pluggable.php");
								foreach ($resultData AS $rk => $ritem) {
									$postCheckMobile = null;
									$postCheckTablet = null;
									$postCheckDesktop = null;

									if (!empty($ritem['types'])) {
									    foreach ($ritem['types'] as $type) {
                                            // @codingStandardsIgnoreStart
										    switch ($type) {
											    case 'mobile':
												    $postCheckMobile  = $wpdb->get_var(
                                                            $wpdb->prepare("SELECT id FROM `{$wpPrefix}posts` WHERE post_type = %s AND post_title = %s",
                                                                "rb_block_mobile_new", $ritem["blockId"]));
												    $resultTypes['mobile'] = true;
												    break;
											    case 'tablet':
												    $postCheckTablet = $wpdb->get_var(
                                                            $wpdb->prepare("SELECT id FROM `{$wpPrefix}posts` WHERE post_type = %s AND post_title = %s",
                                                                "rb_block_tablet_new", $ritem["blockId"]));
												    $resultTypes['tablet'] = true;
												    break;
											    case 'desktop':
												    $postCheckDesktop = $wpdb->get_var(
                                                            $wpdb->prepare("SELECT id FROM `{$wpPrefix}posts` WHERE post_type = %s AND post_title = %s",
                                                                "rb_block_desktop_new", $ritem["blockId"]));
												    $resultTypes['desktop'] = true;
												    break;
										    }
                                            // @codingStandardsIgnoreEnd
                                        }
                                    }

									$postContent = $ritem['code'];
									$postContent = htmlspecialchars_decode($postContent);
									$postContent = preg_replace('~<script~', '<scr_pt_open;', $postContent);
									$postContent = preg_replace('~/script~', '/scr_pt_close;', $postContent);
									$postContent = preg_replace('~<~', 'corner_open;', $postContent);
									$postContent = preg_replace('~>~', 'corner_close;', $postContent);

									if (in_array('mobile', $ritem['types'])) {
										if (!empty($postCheckMobile)) {
											$postarr = ['ID' => $postCheckMobile, 'post_content' => $postContent];
											wp_update_post($postarr, true);
										} else {
											$postarr = [
												'post_content' => $postContent,
												'post_title'   => $ritem['blockId'],
												'post_status'  => "publish",
												'post_type'    => 'rb_block_mobile_new',
												'post_author'  => 0
											];
											wp_insert_post($postarr, true);
										}
									}
									if (in_array('tablet', $ritem['types'])) {
										if (!empty($postCheckTablet)) {
											$postarr = ['ID' => $postCheckTablet, 'post_content' => $postContent];
											wp_update_post($postarr, true);
										} else {
											$postarr = [
												'post_content' => $postContent,
												'post_title'   => $ritem['blockId'],
												'post_status'  => "publish",
												'post_type'    => 'rb_block_tablet_new',
												'post_author'  => 0
											];
											wp_insert_post($postarr, true);
										}
									}
									if (in_array('desktop', $ritem['types'])) {
										if (!empty($postCheckDesktop)) {
											$postarr = ['ID' => $postCheckDesktop, 'post_content' => $postContent];
											wp_update_post($postarr, true);
										} else {
											$postarr = [
												'post_content' => $postContent,
												'post_title'   => $ritem['blockId'],
												'post_status'  => "publish",
												'post_type'    => 'rb_block_desktop_new',
												'post_author'  => 0
											];
											wp_insert_post($postarr, true);
										}
									}
								}
								unset($rk,$ritem);

								RFWP_Cache::setCacheTimeout();
								if (!empty($resultTypes['mobile'])) {
									RFWP_Cache::setMobileCache();
								}
								if (!empty($resultTypes['tablet'])) {
									RFWP_Cache::setTabletCache();
								}
								if (!empty($resultTypes['desktop'])) {
									RFWP_Cache::setDesktopCache();
								}
								RFWP_Cache::deleteActiveCache();
							}
						}
					} elseif(is_wp_error($jsonResult)) {
						$error                                  = $jsonResult->get_error_message();
						$messageFLog                            = 'Saving code for cache error: '.$error.';';
                        RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					}

					return true;
				} catch (Exception $e) {
					$messageFLog = 'Some error in saving code for cache: '.$e->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
					RFWP_Cache::deleteActiveCache();
					return false;
				}
			}
		}
		if (!function_exists('RFWP_tokenMDValidate')) {
			function RFWP_tokenMDValidate($token) {
				if (strlen($token) != 32) {
					return false;
				}
				preg_match('~[^0-9a-z]+~', $token, $validateMatch);
				if (!empty($validateMatch)) {
					return false;
				}

				return true;
			}
		}
		if (!function_exists('RFWP_tokenTimeUpdateChecking')) {
			function RFWP_tokenTimeUpdateChecking($token, $wpPrefix) {
				global $wpdb;
				try {
                    if (empty($GLOBALS['tokenTimeUpdate'])) {
                        // @codingStandardsIgnoreStart
                        $timeUpdate = $wpdb->get_results($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` WHERE optionName = %s",
                            "token_sync_time"));
                        if (empty($timeUpdate)) {
                            $updateResult = RFWP_wpRealbigSettingsTableUpdateFunction($wpPrefix);
                            if ($updateResult == true) {
                                $timeUpdate = $wpdb->get_results($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` WHERE optionName = %s",
                                    "token_sync_time"));
                            }
                        }
                        // @codingStandardsIgnoreEnd
                        if (!empty($token) && $token != 'no token' && ((!empty($GLOBALS['tokenStatusMessage']) &&
                                    ($GLOBALS['tokenStatusMessage'] == 'Синхронизация прошла успешно' ||
                                        $GLOBALS['tokenStatusMessage'] == 'Не нашло позиций для блоков на указанном сайте, ' .
                                        'добавьте позиции для сайтов на странице настроек плагина')) ||
                                empty($GLOBALS['tokenStatusMessage'])) && !empty($timeUpdate)) {
                            if (!empty($timeUpdate)) {
                                $timeUpdate                 = get_object_vars($timeUpdate[0]);
                                $GLOBALS['tokenTimeUpdate'] = $timeUpdate['optionValue'];
                                $GLOBALS['statusColor']     = 'green';
                            } else {
                                $GLOBALS['tokenTimeUpdate'] = '';
                                $GLOBALS['statusColor']     = 'red';
                            }
                        } else {
                            $GLOBALS['tokenTimeUpdate'] = 'never';
                            $GLOBALS['statusColor']     = 'red';
                        }
                    }
				} catch (Exception $e) {
//				echo $e;
					$messageFLog = 'Some error in token time update check: '.$e->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				}
			}
		}
	}
	if (!function_exists('RFWP_tokenChecking')) {
		function RFWP_tokenChecking($wpPrefix) {
			try {
			    if (!empty($GLOBALS['token'])&&$GLOBALS['token']!='no token') {
				    $token = $GLOBALS['token'];
                } else {
				    global $wpdb;
				    $GLOBALS['tokenStatusMessage'] = null;
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				    $token = $wpdb->get_results($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` WHERE optionName = %s",
                        "_wpRealbigPluginToken"));

				    if (!empty($token)) {
					    $token            = get_object_vars($token[0]);
					    $GLOBALS['token'] = $token['optionValue'];
					    $token            = $token['optionValue'];
				    } else {
					    $GLOBALS['token'] = 'no token';
					    $token            = 'no token';
					    $messageFLog = 'Token check: '.$token.';';
                        RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				    }
                }

				return $token;
			} catch (Exception $e) {
				$messageFLog = 'Some error in token check: '.$e->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return 'no token';
			}
		}
	}
	if (!function_exists('RFWP_statusGathererConstructorOld')) {
		function RFWP_statusGathererConstructorOld($pointer) {
			global $wpdb;

			try {
				$statusGatherer        = [];
				$realbigStatusGatherer = get_option('realbig_status_gatherer');

				if ( $pointer == false ) {
					$statusGatherer['element_column_values']           = false;
					$statusGatherer['realbig_plugin_settings_table']   = false;
					$statusGatherer['realbig_settings_table']          = false;
					$statusGatherer['realbig_plugin_settings_columns'] = false;
					if (!empty($realbigStatusGatherer)) {
						$statusGatherer['update_status_gatherer'] = true;
					} else {
						$statusGatherer['update_status_gatherer'] = false;
					}

					return $statusGatherer;
				} else {
					if (!empty($realbigStatusGatherer)) {
						$realbigStatusGatherer                             = json_decode($realbigStatusGatherer, true);
						$statusGatherer['element_column_values']           = $realbigStatusGatherer['element_column_values'];
						$statusGatherer['realbig_plugin_settings_table']   = $realbigStatusGatherer['realbig_plugin_settings_table'];
						$statusGatherer['realbig_settings_table']          = $realbigStatusGatherer['realbig_settings_table'];
						$statusGatherer['realbig_plugin_settings_columns'] = $realbigStatusGatherer['realbig_plugin_settings_columns'];
						$statusGatherer['update_status_gatherer']          = true;

						return $statusGatherer;
					} else {
						$statusGatherer['element_column_values']           = false;
						$statusGatherer['realbig_plugin_settings_table']   = false;
						$statusGatherer['realbig_settings_table']          = false;
						$statusGatherer['realbig_plugin_settings_columns'] = false;
						$statusGatherer['update_status_gatherer']          = false;

						return $statusGatherer;
					}
				}
			} catch (Exception $exception) {
				$messageFLog = 'Some error in token time update check: '.$exception->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return $statusGatherer = [];
			} catch (Error $error) {
				$messageFLog = 'Some error in token time update check: '.$error->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return $statusGatherer = [];
			}
		}
	}
	if (!function_exists('RFWP_statusGathererConstructor')) {
		function RFWP_statusGathererConstructor($pointer) {
			global $wpdb;

			try {
				$statusGatherer        = [];
				$realbigStatusGatherer = get_option('realbig_status_gatherer');

				if ($pointer == false) {
					if (!empty($realbigStatusGatherer)) {
						$statusGatherer['update_status_gatherer'] = true;
					}
				} else {
					if (!empty($realbigStatusGatherer)) {
						$realbigStatusGatherer                             = json_decode($realbigStatusGatherer, true);
						foreach ($realbigStatusGatherer AS $k => $item) {
							$statusGatherer[$k] = $item;
						}
						unset($k, $item);
						$statusGatherer['update_status_gatherer']          = true;
					}
				}
				return $statusGatherer;
			} catch (Exception $exception) {
				$messageFLog = 'Some error in token time update check: '.$exception->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return $statusGatherer = [];
			} catch (Error $error) {
				$messageFLog = 'Some error in token time update check: '.$error->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				return $statusGatherer = [];
			}
		}
	}
	if (!function_exists('RFWP_getPageTypes')) {
		function RFWP_getPageTypes() {
			return [
				'is_home' => 'is_home',
				'is_front_page' => 'is_front_page',
				'is_page' => 'is_page',
				'is_single' => 'is_single',
				'is_singular' => 'is_singular',
				'is_archive' => 'is_archive',
				'is_category' => 'is_category',
			];
		}
	}
    /** Auto Sync */
	if (!function_exists('RFWP_autoSync')) {
		function RFWP_autoSync() {
            RFWP_Cache::setProcessCache();
			global $wpdb;
			$token      = RFWP_tokenChecking($GLOBALS['table_prefix']);
			RFWP_cronCheckLog('cron going to sync 2');

			if (!isset($GLOBALS['RFWP_synchronize_vars'])) {
				$GLOBALS['RFWP_synchronize_vars'] = [];
				$GLOBALS['RFWP_synchronize_vars']['token'] = $token;
				$GLOBALS['RFWP_synchronize_vars']['sameTokenResult'] = true;
				$GLOBALS['RFWP_synchronize_vars']['type'] = 'ajax';
			}

			RFWP_synchronizeLaunchAdd();
		}
	}
	/** End of auto Sync */
	/** Creating Cron RB auto sync */
	if (!function_exists('RFWP_cronAutoGatheringLaunch')) {
		function RFWP_cronAutoGatheringLaunch() {
            RFWP_cronAutoSyncDelete();

			add_filter('cron_schedules', 'rb_addCronAutosync');
			if (!wp_next_scheduled('rb_cron_hook')) {
                wp_schedule_event(time() + RFWP_getPeriodSync(), 'autoSync', 'rb_cron_hook');
			}

            if (!is_admin() && empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON))
                && empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX') && DOING_AJAX))) {
                RFWP_WorkProgressLog(false,'auto sync cron create');
            }
		}
	}
	if (!function_exists('rb_addCronAutosync')) {
		function rb_addCronAutosync($schedules) {
		    $interval = RFWP_getPeriodSync();

            $schedules['autoSync'] = array(
				'interval' => intval($interval),
				'display'  => esc_html__( 'autoSync' ),
			);
			return $schedules;
		}
	}
	if (!function_exists('RFWP_cronAutoSyncDelete')) {
		function RFWP_cronAutoSyncDelete() {
			$checkIt = wp_next_scheduled('rb_cron_hook');

			if ($checkIt) {
                wp_unschedule_event( $checkIt, 'rb_cron_hook' );
            }
		}
	}
    if (!function_exists('RFWP_getPeriodSync')) {
        function RFWP_getPeriodSync($default=5) {
            global $rb_periodSync;

            if (!$rb_periodSync) {
                $rb_periodSync = RFWP_Utils::getFromRbSettings('periodSync');
            }

            if (!$rb_periodSync) {
                $rb_periodSync = $default;
            }

            return $rb_periodSync * 60;
        }
    }

    /** End of Creating Cron RB auto sync */
	if (!function_exists('RFWP_getMenuList')) {
		function RFWP_getMenuList() {
			$menuMap = [];
			try {
				$menuTerms = get_terms(['taxonomy' => 'nav_menu', 'hide_empty' => true]);
				if (!empty($menuTerms)) {
					foreach ($menuTerms AS $k => $item) {
						$menuMap[$item->term_id] = $item->name;
					}
					unset($k,$item);
				}
			} catch (Exception $ex) {} catch (Error $er) {}
			return $menuMap;
		}
	}
	if (!function_exists('RFWP_otherInfoGather')) {
		function RFWP_otherInfoGather() {
			$result = [];
			$result['permalinkStatus'] = RFWP_checkPermalink();
//			$result['thumbnailSizes'] = RFWP_getThumbnailsSizes();
			$result['thumbnailSizes'] = RFWP_getSavedThemeThumbnailSizes();
			$result['taxonomies'] = RFWP_getTaxonomies();
			$result['home_url'] = home_url();
			$turboRssUrls = RFWP_generateTurboRssUrls();
			if (!empty($turboRssUrls)) {
			    if (!empty($turboRssUrls['mainRss'])) {
				    $result['mainRss'] = $turboRssUrls['mainRss'];
                }
			    if (!empty($turboRssUrls['trashRss'])) {
				    $result['trashRss'] = $turboRssUrls['trashRss'];
                }
            }

			return $result;
		}
	}
	if (!function_exists('RFWP_checkPermalink')) {
		function RFWP_checkPermalink() {
			$result = false;
			if (get_option('permalink_structure')) {
				$result = true;
			}
			return $result;
		}
	}
	if (!function_exists('RFWP_checkModules')) {
		function RFWP_checkModules() {
//			error_log(PHP_EOL.current_time('mysql').': '.$messageFLog.PHP_EOL, 3, $rb_logFile);
		}
	}
    if (!function_exists('RFWP_fillRotatorFileInfo')) {
        function RFWP_fillRotatorFileInfo($rotatorFileInfo) {
            $partsArray = [];
            if (!empty(WP_CONTENT_DIR)&&!empty(WP_CONTENT_URL)) {
                $parts = [
                    'path' => WP_CONTENT_DIR,
                    'pathAdditional' => '/',
                    'url' => WP_CONTENT_URL,
                    'urlAdditional' => '/',
                ];
                array_push($partsArray, $parts);
            }
            if (!empty(WP_PLUGIN_DIR)&&!empty(WP_PLUGIN_URL)) {
                $parts = [
                    'path' => WP_PLUGIN_DIR,
                    'pathAdditional' => '/',
                    'url' => WP_PLUGIN_URL,
                    'urlAdditional' => '/',
                ];
                array_push($partsArray, $parts);
            }
            $parts = [
                'path' => dirname(__FILE__),
                'pathAdditional' => '/',
                'url' => plugin_dir_url(__FILE__),
                'urlAdditional' => '',
            ];
            array_push($partsArray, $parts);
            $rotatorFileInfo['pathUrlToFolderParts'] = $partsArray;

            return $rotatorFileInfo;
        }
    }
    if (!function_exists('RFWP_checkRotatorFile')) {
        function RFWP_checkRotatorFile($rotatorFileInfo) {
            foreach ($rotatorFileInfo['pathUrlToFolderParts'] as $k => $item) {
                $pathToFile = $item['path'].$item['pathAdditional'].$GLOBALS['rb_variables']['rotator'].'.js';
	            $urlToFile = $item['url'].$item['urlAdditional'].$GLOBALS['rb_variables']['rotator'].'.js';
	            $checkCurrentRotator = RFWP_checkRotatorFileSingle($pathToFile, $urlToFile);
	            if (!empty($checkCurrentRotator)) {
		            $clearedUrl = RFWP_clearUrl($item['url']);
		            $urlToFile = $clearedUrl.$item['urlAdditional'].$GLOBALS['rb_variables']['rotator'].'.js';
		            $rotatorFileInfo['urlToFile'] = $urlToFile;
		            break;
                }
            }
            unset($k,$item);

            return $rotatorFileInfo;
        }
    }
    if (!function_exists('RFWP_checkRotatorFileSingle')) {
        function RFWP_checkRotatorFileSingle($pathToFile, $urlToFile) {
            if (file_exists($pathToFile)) {
                $checkLocalRotatorAccessibility = RFWP_checkLocalRotatorAccessibility($urlToFile);
                if (!empty($checkLocalRotatorAccessibility)) {
                    return true;
                }
            }
            unset($k,$item);

            return false;
        }
    }
	if (!function_exists('RFWP_createAndFillLocalRotator')) {
		function RFWP_createAndFillLocalRotator($rotatorFileInfo) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
            global $wp_filesystem;

            try {
                $rotatorFileInfo['checkFileExists'] = false;
                foreach ($rotatorFileInfo['pathUrlToFolderParts'] as $k => $item) {
                    $pathToFile = $item['path'].$item['pathAdditional'].$GLOBALS['rb_variables']['rotator'].'.js';
                    $urlToFile = $item['url'].$item['urlAdditional'].$GLOBALS['rb_variables']['rotator'].'.js';
                    try {
	                    $arrContextOptions=array(
		                    "ssl"=>array(
			                    "verify_peer"=>false,
			                    "verify_peer_name"=>false,
		                    ),
	                    );

                        $response = wp_remote_get($rotatorFileInfo['urlToRotator'], ["sslverify" => false]);

	                    $rotatorFileInfo['fileRotatorContent'] = wp_remote_retrieve_body($response);
                    } catch (Exception $ex) {
                        $fileGetContentError = true;
                    } catch (Error $er) {
                        $fileGetContentError = true;
                    }

                    if (!empty($rotatorFileInfo['fileRotatorContent'])) {
                        $wp_filesystem->put_contents($pathToFile, $rotatorFileInfo['fileRotatorContent']);
                    }

	                $checkResult = RFWP_checkRotatorFileSingle($pathToFile,$urlToFile);
                    if (!empty($checkResult)) {
	                    $rotatorFileInfo['pathToFile'] = $pathToFile;
	                    $urlToFile = RFWP_clearUrl($urlToFile);
	                    $rotatorFileInfo['urlToFile'] = $urlToFile;
	                    global $wpdb;
	                    $wpPrefix = RFWP_getTablePrefix();
                        RFWP_Utils::saveToRbSettings($urlToFile, 'localRotatorUrl');
	                    $GLOBALS['rb_variables']['localRotatorUrl'] = $urlToFile;
	                    set_transient(RFWP_Variables::LOCAL_ROTATOR_GATHER, true, 15*60);
	                    $GLOBALS['rb_variables'][RFWP_Variables::LOCAL_ROTATOR_GATHER] = null;
	                    break;
                    }
                }
                unset($k,$item);
			} catch (Exception $ex) {
				$messageFLog = 'Some error in RFWP_createAndFillLocalRotator: '.$ex->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			} catch (Error $er) {
				$messageFLog = 'Some error in RFWP_createAndFillLocalRotator: '.$er->getMessage().';';
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
			}
		    return $rotatorFileInfo;
		}
	}
	if (!function_exists('RFWP_generateTurboRssUrls')) {
		function RFWP_generateTurboRssUrls() {
            $turboOptions = [];
		    if (function_exists('RFWP_rssOptionsGet')) {
			    $turboOptions = RFWP_rssOptionsGet();
			    if (!empty($turboOptions))
			    {
				    $turboUrl = $turboOptions['name'];
				    if (get_option('permalink_structure')) {
					    $url = home_url().'/feed/'.$turboUrl.'/';
					    $trashUrl = $url.'?rb_rss_trash=1';
				    } else {
					    $url = home_url().'/?feed='.$turboUrl;
					    $trashUrl = $url.'&rb_rss_trash=1';
				    }
                    $turboOptions['mainRss'] = $url;
                    $turboOptions['trashRss'] = $trashUrl;
			    }
            }

			return $turboOptions;
        }
    }
	if (!function_exists('RFWP_getDomain')) {
		function RFWP_getDomain() {
			$urlData = '';
			if (!empty($_SERVER['HTTP_HOST'])) {
				$urlData = $_SERVER['HTTP_HOST'];
			} elseif (!empty($_SERVER['SERVER_NAME'])) {
				$urlData = $_SERVER['SERVER_NAME'];
			}

			return $urlData;
        }
    }
	if (!function_exists('RFWP_checkLocalRotatorAccessibility')) {
	    function RFWP_checkLocalRotatorAccessibility($urlToCheck) {
		    $checkResult = false;
		    try {
			    $checkResult = wp_get_http_headers($urlToCheck);
		    }
		    catch (Exception $ex) {
			    $errorText = __FUNCTION__." error: ".$ex->getMessage();
			    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
		    }
		    catch (Error $ex) {
			    $errorText = __FUNCTION__." error: ".$ex->getMessage();
			    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
		    }

	        return $checkResult;
        }
    }
	if (!function_exists('RFWP_pluginActivation')) {
	    function RFWP_pluginActivation() {
	        //here
        }
    }
	if (!function_exists('RFWP_createLocalRotator')) {
	    function RFWP_createLocalRotator() {
		    try {
                $rotatorFileInfo = [];
                $rotatorFileInfo['pathToFile'] = '';
                $rotatorFileInfo['urlToFile'] = '';
                $rotatorFileInfo = RFWP_fillRotatorFileInfo($rotatorFileInfo);
                $rotatorFileInfo['urlToRotator'] = 'https://'.$GLOBALS['rb_variables']['adDomain'].'/'.$GLOBALS['rb_variables']['rotator'].'.min.js';
                $rotatorFileInfo = RFWP_createAndFillLocalRotator($rotatorFileInfo);
		    }
		    catch (Exception $ex) {
			    $errorText = __FUNCTION__." error: ".$ex->getMessage();
			    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
		    }
		    catch (Error $ex) {
			    $errorText = __FUNCTION__." error: ".$ex->getMessage();
			    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
		    }

            return false;
        }
    }
	if (!function_exists('RFWP_clearUrl')) {
	    function RFWP_clearUrl($url) {
		    $clearedUrl = $url;
		    try {
			    $clearedUrl = preg_replace('~^http[s]?\:~ius', '', $url);
			    if (empty($clearedUrl)) {
				    $clearedUrl = $url;
			    }
		    }
		    catch (Exception $ex) {
			    $errorText = __FUNCTION__." error: ".$ex->getMessage();
			    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
		    }
		    catch (Error $ex) {
			    $errorText = __FUNCTION__." error: ".$ex->getMessage();
			    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
		    }

		    return $clearedUrl;
        }
    }

	if (!function_exists('RFWP_getWpPrefix')) {
		function RFWP_getWpPrefix() {
			$wpPrefix = '';
			try {
				if (!empty($GLOBALS['wpPrefix'])) {
					$wpPrefix = $GLOBALS['wpPrefix'];
				} else {
					if (!empty($GLOBALS['table_prefix'])) {
						$wpPrefix = $GLOBALS['table_prefix'];
					} else {
						global $wpdb;
						$wpPrefix = $wpdb->base_prefix;
					}
					if (!empty($wpPrefix)) {
						$GLOBALS['wpPrefix'] = $wpPrefix;
                    }
                }

				if (empty($wpPrefix)) {
					$errorText = "wpdb prefix missing";
					RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
				}
			}
			catch (Exception $ex) {
				$errorText = __FUNCTION__." error: ".$ex->getMessage();
				RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
			}
			catch (Error $ex) {
				$errorText = __FUNCTION__." error: ".$ex->getMessage();
				RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $errorText);
			}

			return $wpPrefix;
		}
    }
	if (!function_exists('RFWP_getThumbnailsSizes')) {
	    function RFWP_getThumbnailsSizes() {
		    $thumbnailsSizes = get_intermediate_image_sizes();

		    return $thumbnailsSizes;
        }
    }
	if (!function_exists('RFWP_synchronizeLaunch')) {
	    function RFWP_synchronizeLaunch() {
		    RFWP_synchronize($GLOBALS['RFWP_synchronize_vars']['token'],
                $GLOBALS['RFWP_synchronize_vars']['sameTokenResult'], $GLOBALS['RFWP_synchronize_vars']['type'],
                isset($GLOBALS['RFWP_synchronize_vars']['updateLogs']) ? $GLOBALS['RFWP_synchronize_vars']['updateLogs'] : null);
        }
    }
	if (!function_exists('RFWP_synchronizeManualLaunchAdd')) {
	    function RFWP_synchronizeLaunchAdd() {
		    add_action('wp_loaded', 'RFWP_synchronizeLaunch');
//		    add_action('init', 'RFWP_synchronizeLaunch', 9999);
        }
    }
	if (!function_exists('RFWP_saveThemeThumbnailSizes')) {
	    function RFWP_saveThemeThumbnailSizes() {
	        global $wpdb;

		    $thumbnailsSizes = RFWP_getThumbnailsSizes();
		    $thumbnailsSizes = wp_json_encode($thumbnailsSizes);
            RFWP_Utils::saveToRbSettings($thumbnailsSizes,'thumbnailsSizes');

	        return true;
        }
    }
	if (!function_exists('RFWP_getSavedThemeThumbnailSizes')) {
	    function RFWP_getSavedThemeThumbnailSizes() {
		    $thumbnailsSizes = RFWP_Utils::getFromRbSettings('thumbnailsSizes');
		    if (!empty($thumbnailsSizes)) {
		        if (is_string($thumbnailsSizes)) {
			        $thumbnailsSizes = json_decode($thumbnailsSizes, true);
                } else {
		            $thumbnailsSizes = [];
                }
            }

		    return $thumbnailsSizes;
        }
    }
	if (!function_exists('RFWP_getSyncDomain')) {
		function RFWP_getSyncDomain() {
			global $syncDomain;

			if (empty($syncDomain)) {
				global $wpdb;
				global $wpPrefix;

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$syncDomain = $wpdb->get_var($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` WGPS WHERE optionName = %s",
                    "sync_domain"));
			}

            if (empty($syncDomain)) {
                $syncDomain = 'wp.realbig.media';
            }

			return $syncDomain;
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

        RFWP_Utils::saveToRbSettings('synchro: ' . $ex->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

	deactivate_plugins(plugin_basename(__FILE__));
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

        RFWP_Utils::saveToRbSettings('synchro: ' . $er->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($er); ?></div><?php
}