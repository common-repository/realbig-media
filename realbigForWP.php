<?php

if (!defined("ABSPATH")) { exit;}

/*
Plugin name:  Realbig Media
Description:  Плагин для монетизации от RealBig.media
Version:      1.1.2
Author:       Realbig Team
Author URI:   https://realbig.media
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

require_once (ABSPATH."/wp-admin/includes/plugin.php");


$res = true;

$res = $res && include_once(plugin_dir_path(__FILE__) . "RFWP_Variables.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "RFWP_Logs.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "RFWP_CachePlugins.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "RFWP_Cache.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "update.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "synchronising.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "textEditing.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "syncApi.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "RFWP_Amp.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "RFWP_Utils.php");
$res = $res && include_once(plugin_dir_path(__FILE__) . "RFWP_Punycode.php");

if (empty($res)) {
    return false;
}

try {
	/** **************************************************************************************************************** **/
	global $wpdb;
	global $table_prefix;

	if (!isset($GLOBALS['dev_mode'])) {
        $devMode = false;
		$GLOBALS['dev_mode'] = $devMode;
    }
	if (!isset($GLOBALS['rb_testMode'])) {
		RFWP_initTestMode();
    }
	if (!isset($GLOBALS['rb_enableLogs'])) {
		RFWP_Logs::initEnableLogs();
    }
    if (!isset($GLOBALS['rb_localRotator'])) {
        $GLOBALS['rb_localRotator'] = true;
    }
	include_once (plugin_dir_path(__FILE__).'rssGenerator.php');

	if (function_exists('wp_cookie_constants')) {
		wp_cookie_constants();
	}

	if (!isset($GLOBALS['rb_variables'])) {
	    $GLOBALS['rb_variables'] = [];
    }
    if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
	    RFWP_WorkProgressLog(false,'begin of process');
    }

	if (empty(apply_filters('wp_doing_cron', defined('DOING_CRON')&&DOING_CRON))) {
		require_once (ABSPATH."/wp-includes/pluggable.php");
		$curUserCan = current_user_can('activate_plugins');
	}

	if (!empty($GLOBALS['dev_mode'])) {
		if (empty($GLOBALS['rb_admin_menu_loaded'])) {
			require_once(plugin_dir_path(__FILE__)."adminMenuAdd.php");
			$GLOBALS['rb_admin_menu_loaded'] = true;
		}
	}
	if (!isset($GLOBALS['wpPrefix'])) {
		RFWP_getWpPrefix();
	}
    if (!isset($GLOBALS['excludedPagesChecked'])) {
	    $GLOBALS['excludedPagesChecked'] = false;
    }

	if (!isset($GLOBALS['rb_variables']['rotator'])||!isset($GLOBALS['rb_variables']['adDomain'])||!isset($GLOBALS['rb_variables']['localRotatorUrl'])) {
		$GLOBALS['rb_variables']['adDomain'] = 'newrrb.bid';
		$GLOBALS['rb_variables']['rotator'] = null;
		$GLOBALS['rb_variables']['localRotatorUrl'] = null;
		$GLOBALS['rb_variables']['adWithStatic'] = null;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$getOV = $wpdb->get_results("SELECT optionName, optionValue FROM `{$GLOBALS['wpPrefix']}realbig_settings` " .
            "WHERE optionName IN ('domain','rotator','localRotatorUrl','adWithStatic')");
		if (!empty($getOV)) {
			foreach ($getOV AS $k => $item) {
				if (!empty($item->optionValue)) {
				    switch ($item->optionName) {
                        case 'domain':
	                        $GLOBALS['rb_variables']['adDomain'] = $item->optionValue;
                            break;
                        case 'rotator':
	                        $GLOBALS['rb_variables']['rotator'] = $item->optionValue;
                            break;
                        case 'localRotatorUrl':
	                        $GLOBALS['rb_variables']['localRotatorUrl'] = $item->optionValue;
                            break;
                        case 'adWithStatic':
	                        $GLOBALS['rb_variables']['adWithStatic'] = $item->optionValue;
                            break;
                    }
				}
			}
		}
		unset($k, $item, $getOV);
	}
//	if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {}
	/***************** Test zone ******************************************************************************************/
	if (!empty($devMode)&&!is_admin()) {
		include_once (plugin_dir_path(__FILE__)."testFunctions.php");
	}
	$ampCheckResult = RFWP_Amp::detectAmpPage();
	/** Rss init */
	if (function_exists('RFWP_rssInit')) {
		add_action('init', 'RFWP_rssInit');
    }

	/** End of Rss init */
    /** Check in header inserting */
    if (!empty($curUserCan)&&!empty($devMode)&&function_exists('RFWP_checkHeader')) {
	    add_action('wp_head', 'RFWP_checkHeader', 1002);
    }
    /** End of Check in header inserting */
	/***************** End of test zone ***********************************************************************************/
	/** Rotator file creation */
    if (!empty($GLOBALS['rb_localRotator'])
        &&!empty($GLOBALS['rb_variables']['rotator'])
        &&!empty($GLOBALS['rb_variables']['adDomain'])
    ) {
	    if (((!empty($_POST["_csrf"]) && wp_verify_nonce($_POST["_csrf"], RFWP_Variables::CSRF_ACTION) && !empty($_POST['action']) && $_POST['action'] == 'heartbeat')
                || !empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON))) && !isset($GLOBALS['rb_variables'][RFWP_Variables::LOCAL_ROTATOR_GATHER])) {
		    $GLOBALS['rb_variables'][RFWP_Variables::LOCAL_ROTATOR_GATHER] = get_transient(RFWP_Variables::LOCAL_ROTATOR_GATHER);
	    }
        if ((!empty($_POST["_csrf"]) && wp_verify_nonce($_POST["_csrf"], RFWP_Variables::CSRF_ACTION) && !empty($_POST['saveTokenButton']))
            ||(isset($GLOBALS['rb_variables'][RFWP_Variables::LOCAL_ROTATOR_GATHER])&&empty($GLOBALS['rb_variables'][RFWP_Variables::LOCAL_ROTATOR_GATHER]))
        ) {
            RFWP_createLocalRotator();
        }
    }
	/** End of Rotator file creation */
	/** Functions zone *********************************************************************************************************************************************************************/
	if (empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON))) {
		/********** Cached AD blocks saving **********************************************************************************/
        if (!function_exists('saveAdBlocks')) {
            function saveAdBlocks($tunnelData) {
                include_once(plugin_dir_path(__FILE__) . "RFWP_Variables.php");

                if (!empty($_POST['_csrf']) && wp_verify_nonce($_POST['_csrf'], RFWP_Variables::CSRF_USER_JS_ACTION)
                        && !empty($_POST['type']) && $_POST['type']=='blocksGethering') {
                    include_once (plugin_dir_path(__FILE__).'connectTestFile.php');
                }
                return $tunnelData;
            }
        }
        if (!function_exists('setLongCache')) {
            function setLongCache($tunnelData) {
                include_once(plugin_dir_path(__FILE__) . "RFWP_Variables.php");

                if (!empty($_POST['_csrf']) && wp_verify_nonce($_POST['_csrf'], RFWP_Variables::CSRF_USER_JS_ACTION)
                        && !empty($_POST['type'])&&$_POST['type']=='longCatching') {
                    RFWP_Cache::setLongCache();
                }
                return $tunnelData;
            }
        }
		/********** End of cached AD blocks saving ***************************************************************************/
		/********** New working system ***************************************************************************************/
		if (!function_exists('RFWP_blocks_in_head_add')) {
			function RFWP_blocks_in_head_add() {
				try {
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'blocks_in_head_add begin');
					}
					$content = RFWP_shortCodesAdd('');
					$fromDb = RFWP_gatherBlocksFromDb();
					$GLOBALS['fromDb'] = $fromDb;
					$contentBlocks = RFWP_creatingJavascriptParserForContentFunction_test($fromDb['adBlocks'], $fromDb['excIdClass'], $fromDb['blockDuplicate'], $fromDb['obligatoryMargin'], $fromDb['tagsListForTextLength'], $fromDb['showAdsNoElement']);
					$content = $contentBlocks['before'].$content.$contentBlocks['after'];
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'blocks_in_head_add end');
					}

                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?><?php echo $content ?><?php
				} catch (Exception $ex) {
					$messageFLog = 'RFWP_blocks_in_head_add errors: '.$ex->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				} catch (Error $er) {
					$messageFLog = 'RFWP_blocks_in_head_add errors: '.$er->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				}
			}
		}
		if (!function_exists('RFWP_launch_without_content')) {
			function RFWP_launch_without_content() {
				try {
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'launch_without_content begin');
					}
					$content = '';
					$content = RFWP_launch_without_content_function($content);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?><?php echo $content ?><?php
				} catch (Exception $ex) {
					$messageFLog = 'RFWP_launch_without_content errors: '.$ex->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				} catch (Error $er) {
					$messageFLog = 'RFWP_launch_without_content errors: '.$er->getMessage().';';
                    RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
				}
			}
		}
		/********** End of New working system ********************************************************************************/
		/********** Add classes from block div *******************************************************************************/
        if (!function_exists('RFWP_block_classes_add')) {
            function RFWP_block_classes_add() {
                echo '<script>
    var block_classes = ["content_rb", "cnt32_rl_bg_str", "rl_cnt_bg"];

    function addAttrItem(className) {
        if (document.querySelector("." + className) && !block_classes.includes(className)) {
            block_classes.push(className);
        }
    }
</script>';
            }
        }
		/********** End of Add classes from block div ************************************************************************/
		/********** Adding AD code in head area ******************************************************************************/
		if (!function_exists('RFWP_AD_header_add')) {
			function RFWP_AD_header_add() {
				global $wpdb;
				$getDomain = 'newrrb.bid';
				$getRotator = 'f6ds8jhy56';

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$getOV = $wpdb->get_results($wpdb->prepare("SELECT optionName, optionValue FROM `{$GLOBALS['wpPrefix']}realbig_settings` " .
                    "WHERE optionName IN (%s, %s)", "domain", "rotator"));
				foreach ($getOV AS $k => $item) {
					if (!empty($item->optionValue)) {
						if ($item->optionName == 'domain') {
							$getDomain = $item->optionValue;
						} else {
							$getRotator = $item->optionValue;
						}
					}
				}
				unset($k, $item);

				if (!empty($GLOBALS['dev_mode'])&&!empty($GLOBALS['kill_rb'])&&$GLOBALS['kill_rb']==2) {
					$getDomain  = "ex.ua";
				}
				$rotatorUrl = "https://".$getDomain."/".$getRotator.".min.js";
				$GLOBALS['rotatorUrl'] = $rotatorUrl;

				require_once (plugin_dir_path(__FILE__)."textEditing.php");
				$headerParsingResult = RFWP_headerInsertor('ad');
				$longCache = RFWP_Cache::getLongCache();

				if (empty($longCache)) {
					RFWP_launch_cache($getRotator, $getDomain);
//					RFWP_launch_cache_local($getRotator, $getDomain);

					if ($headerParsingResult == true) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo RFWP_get_rotator_code($getRotator, $getDomain);
					}
				}
				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'AD_header_add end');
				}
			}
		}
		if (!function_exists('RFWP_get_rotator_code')){
		    function RFWP_get_rotator_code($rotator, $domain) {
			    global $wpdb;
			    $getCode = '';

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			    $array = $wpdb->get_results($wpdb->prepare("SELECT optionValue FROM `{$GLOBALS['wpPrefix']}realbig_settings` WHERE optionName=%s",
                    "rotatorCode"), ARRAY_A);

			    if (!empty($array[0]['optionValue'])) {
			        $getCode = html_entity_decode($array[0]['optionValue']);
			    }

			    if (empty($getCode))
			    {
			        $getCode = '<script type="text/javascript"> rbConfig = {start: performance.now(),rotator:\'' . $rotator . '\'}; </script>
                    <script type="text/javascript">
                        let rotatorScript = document.createElement(\'script\');
                        rotatorScript.src = "//' .  $domain  . '/' . $rotator . '.min.js";
                        rotatorScript.type = "text/javascript";
                        rotatorScript.async = true;

                        document.head.append(rotatorScript);
                    </script>';
			    }

			    return $getCode;
		    }
		}
		if (!function_exists('RFWP_push_universal_head_add')) {
			function RFWP_push_universal_head_add() {
				require_once (plugin_dir_path(__FILE__)."textEditing.php");
//				$headerParsingResult = RFWP_headerInsertor('push');
//				if ($headerParsingResult == true) {
//					$headerParsingResult = RFWP_headerInsertor('pushNative');
//					if ($headerParsingResult == true) {
//					}
//                }
				$headerParsingResult = RFWP_headerInsertor('pushUniversal');
				if ($headerParsingResult == true) {
					global $wpdb;

					if (isset($GLOBALS['rb_push']['universalDomain'])) {
						$pushDomain = $GLOBALS['rb_push']['universalDomain'];
                    } else {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$pushDomain = $wpdb->get_var($wpdb->prepare("SELECT optionValue FROM `{$GLOBALS['wpPrefix']}realbig_settings` " .
                            "WHERE optionName = %s", "pushUniversalDomain"));
					}
					if (empty($pushDomain)) {
						$pushDomain = 'newup.bid';
					}

					?><script charset="utf-8" async
                              src="<?php echo esc_url("https://{$pushDomain}/pjs/{$GLOBALS['rb_push']['universalCode']}.js") ?>"></script> <?php
				}
				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'push_universal_head_add end');
				}
			}
		}
		if (!function_exists('RFWP_inserts_head_add')) {
			function RFWP_inserts_head_add() {
				$contentToAdd = RFWP_insertsToString('header');
				$stringToAdd = '';
				if (!empty($contentToAdd)) {
					foreach ($contentToAdd AS $k=>$item) {
					    if (!empty($item)&&!empty($item['content'])) {
						    $stringToAdd .= ' '.$item['content'].' ';
                        }
					}
                }
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?><?php echo $stringToAdd ?><?php
				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'inserts_head_add end');
				}
			}
		}
		/********** End of Adding AD code in head area ***********************************************************************/
		/********** Adding insertings in text ********************************************************************************/
		if (!function_exists('RFWP_insertingsToContentAddingFunction')) {
			function RFWP_insertingsToContentAddingFunction($content) {
                if (!empty($GLOBALS['rfwp_is_amp'])) {
                    return $content;
                }
				if (empty($GLOBALS['used_ins'])||(!empty($GLOBALS['used_ins'])&&empty($GLOBALS['used_ins']['body_0']))) {
					$GLOBALS['used_ins']['body_0'] = true;
					$insertings = RFWP_insertsToString('body', 0);
				}
				$content = RFWP_insertingsToContent($content);
				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'RFWP_insertingsToContentAddingFunction end');
				}
				return $content;
			}
		}
		/********** End of Adding insertings in text *************************************************************************/
		/********** Using settings in texts **********************************************************************************/
		if (!function_exists('RFWP_adBlocksToContentInsertingFunction')) {
			function RFWP_adBlocksToContentInsertingFunction($content) {
                if (!empty($GLOBALS['rfwp_is_amp'])) {
                    return $content;
                }

				global $posts;
				if (!empty($posts)&&count($posts) > 0) {
					foreach ($posts AS $k => $item) {
						if (!empty($item->post_type)&&$item->post_type=='tdb_templates') {
							return $content;
						}
					}
					unset($k,$item);
				}

				global $wp_query;
				global $post;

				$fromDb = [];

				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'adBlocksToContentInsertingFunction begin');
				}

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

				if ((!empty($arrayOfCheckedTypes['is_home'])||!empty($arrayOfCheckedTypes['is_front_page']))&&!empty($GLOBALS['pageChecks']['excludedMainPage'])) {
					return $content;
				} elseif (in_array(true, $arrayOfCheckedTypes)) {
					if (!empty($GLOBALS['pageChecks']['excludedPageTypes'])) {
						$excludedPageTypesString = $GLOBALS['pageChecks']['excludedPageTypes'];
						$excludedPageTypes = explode(',', $excludedPageTypesString);
						foreach ($excludedPageTypes AS $k => $item) {
							if (!empty($arrayOfCheckedTypes[$item])) {
								$pasingAllowed = false;
								break;
							}
						}
					}

					if (!empty($pasingAllowed)) {
						global $wpdb;

//			    $excIdClass = $wpdb->get_var('SELECT optionValue FROM '.$GLOBALS['wpPrefix'].'realbig_settings WGPS WHERE optionName = "excludedIdAndClasses"');
						$excIdClass = null;
						$blockDuplicate = 'yes';
						$statusFor404 = 'show';
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
						$realbig_settings_info = $wpdb->get_results($wpdb->prepare("SELECT optionName, optionValue " .
                            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                            "FROM `{$GLOBALS['wpPrefix']}realbig_settings` WGPS WHERE optionName IN (%s, %s, %s)",
                            'excludedIdAndClasses', 'blockDuplicate', 'statusFor404'));
						if (!empty($realbig_settings_info)) {
							foreach ($realbig_settings_info AS $k => $item) {
								if (isset($item->optionValue)) {
								    switch ($item->optionName) {
                                        case 'excludedIdAndClasses':
	                                        $excIdClass = $item->optionValue;
                                            break;
                                        case 'blockDuplicate':
	                                        if ($item->optionValue==0) {
		                                        $blockDuplicate = 'no';
	                                        }
                                            break;
                                        case 'statusFor404':
                                            $statusFor404 = $item->optionValue;
                                            break;
                                    }
								}
							}
							unset($k,$item);
						}

						$cachedBlocks = '';
						if (!isset($GLOBALS['rb_type_device'])) {
							$GLOBALS['rb_type_device'] = RFWP_wp_get_type_device();
						}

						$shortcodesGathered = get_posts(['post_type'=>'rb_shortcodes','numberposts'=>-1]);
						$shortcodes = [];
						foreach ($shortcodesGathered AS $k=>$item) {
							if (empty($shortcodes[$item->post_excerpt])) {
								$shortcodes[$item->post_excerpt] = [];
							}
							$shortcodes[$item->post_excerpt][$item->post_title] = $item;
						}

						if ((!is_404())||$statusFor404!='disable') {
                            // @codingStandardsIgnoreStart
							if (!empty($content)) {
								$fromDb = $wpdb->get_results("SELECT * FROM `{$GLOBALS['wpPrefix']}realbig_plugin_settings` WGPS");
							} else {
								$fromDb = $wpdb->get_results("SELECT * FROM `{$GLOBALS['wpPrefix']}realbig_plugin_settings` WGPS WHERE setting_type = 3");
							}
                            // @codingStandardsIgnoreEnd
                        }

						require_once (plugin_dir_path(__FILE__)."textEditing.php");
						$content = RFWP_addIcons($fromDb, $content);

						if (empty($GLOBALS['used_ins'])||(!empty($GLOBALS['used_ins'])&&empty($GLOBALS['used_ins']['body_1']))) {
							$GLOBALS['used_ins']['body_1'] = true;
							$inserts = RFWP_insertsToString('body', 1);
						}

						add_filter('the_content', 'RFWP_rbCacheGatheringLaunch', 5003);
						if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
							RFWP_WorkProgressLog(false,'adBlocksToContentInsertingFunction end');
						}

						return $content;
					} else {
						if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
							RFWP_WorkProgressLog(false,'adBlocksToContentInsertingFunction empty content end');
						}
					}
				} else {
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'adBlocksToContentInsertingFunction forbidden page type end');
					}
				}
				return $content;
			}
		}
		/********** End of Using settings in texts ***************************************************************************/
		/********** Autosync and JS text edit ********************************************************************************/
		if (!function_exists('RFWP_syncFunctionAdd1')) {
			function RFWP_syncFunctionAdd1() {
				wp_enqueue_script(
					'asyncBlockInserting',
					plugins_url().'/'.basename(__DIR__).'/assets/js/asyncBlockInserting.js',
					array('jquery'),
					$GLOBALS['realbigForWP_version'],
					false
				);

				wp_localize_script(
					'asyncBlockInserting',
					'adg_object_ad',
					array('ajax_url' => admin_url('admin-ajax.php'))
				);

				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'asyncBlockInserting file add');
				}
			}
		}
		if (!function_exists('RFWP_syncFunctionAdd2')) {
			function RFWP_syncFunctionAdd2() {
				wp_enqueue_script(
					'readyAdGather',
					plugins_url().'/'.basename(__DIR__).'/assets/js/readyAdGather.js',
					array('jquery'),
					$GLOBALS['realbigForWP_version'],
					false
				);

				wp_localize_script(
					'readyAdGather',
					'adg_object',
					array('ajax_url' => admin_url('admin-ajax.php'))
				);

				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'readyAdGather file add');
				}
			}
		}
		if (!function_exists('RFWP_syncFunctionAdd11')) {
			function RFWP_syncFunctionAdd11() {
				RFWP_addWebnavozJs();
				if (empty($GLOBALS['rfwp_addedAlready']['asyncBlockInserting'])) {
                    include_once(plugin_dir_path(__FILE__) . "RFWP_Variables.php");

					echo "<script>" . PHP_EOL;
					echo "if (typeof rb_ajaxurl==='undefined') {var rb_ajaxurl = '" . esc_url(admin_url('admin-ajax.php')) . "';}" . PHP_EOL;
					echo "if (typeof rb_csrf==='undefined') {var rb_csrf = '" . esc_html(wp_create_nonce(RFWP_Variables::CSRF_USER_JS_ACTION)) . "';}" . PHP_EOL;

					if (empty(get_transient(RFWP_Variables::GATHER_CONTENT_LONG)) &&
                        empty(get_transient(RFWP_Variables::GATHER_CONTENT_SHORT))) {

                        echo "if (typeof gather_content==='undefined') {var gather_content = true;}" . PHP_EOL;
                    } else {
                        echo "if (typeof gather_content==='undefined') {var gather_content = false;}" . PHP_EOL;
                    }

                    include_once(plugin_dir_path(__FILE__) . 'assets/js/RFWP_BlockInserting.js');
                    include_once(plugin_dir_path(__FILE__) . 'assets/js/asyncBlockInserting.js');
                    echo  PHP_EOL . "</script>" . PHP_EOL;

                    $GLOBALS['rfwp_addedAlready']['asyncBlockInserting'] = true;
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'asyncBlockInserting file add');
					}
				}
			}
		}
		if (!function_exists('RFWP_syncFunctionAdd21')) {
			function RFWP_syncFunctionAdd21() {
				if (empty($GLOBALS['rfwp_addedAlready']['readyAdGather'])) {
                    include_once(plugin_dir_path(__FILE__) . "RFWP_Variables.php");

                    echo "<script>" . PHP_EOL;
                    echo "if (typeof rb_ajaxurl==='undefined') {var rb_ajaxurl = '" . esc_url(admin_url('admin-ajax.php')) . "';}" . PHP_EOL;
                    echo "if (typeof rb_csrf==='undefined') {var rb_csrf = '" . esc_html(wp_create_nonce(RFWP_Variables::CSRF_USER_JS_ACTION)) . "';}" . PHP_EOL;

                    if ((empty(RFWP_Cache::getMobileCache()) || empty(RFWP_Cache::getTabletCache()) ||
                        empty(RFWP_Cache::getDesktopCache())) && empty(RFWP_Cache::getCacheTimeout())) {

                        echo "if (typeof cache_devices==='undefined') {var cache_devices = false;}" . PHP_EOL;
                    } else {
                        echo "if (typeof cache_devices==='undefined') {var cache_devices = true;}" . PHP_EOL;
                    }

                    include_once(plugin_dir_path(__FILE__) . 'assets/js/readyAdGather.js');
                    echo  PHP_EOL . "</script>" . PHP_EOL;

					$GLOBALS['rfwp_addedAlready']['readyAdGather'] = true;
					if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
						RFWP_WorkProgressLog(false,'readyAdGather file add');
					}
				}
			}
		}
		if (!function_exists('RFWP_js_add')) {
			function RFWP_js_add() {
                $jsToHead = RFWP_getJsToHead();
                if (!empty($jsToHead)) {
                    $insertPlace = 'wp_head';
                } else {
                    $insertPlace = 'wp_footer';
                }
//			add_action('wp_enqueue_scripts', 'RFWP_syncFunctionAdd1', 10);
                
				add_action($insertPlace, 'RFWP_syncFunctionAdd11', 10);

				$cacheTimeoutMobile = RFWP_Cache::getMobileCache();
				$cacheTimeoutTablet = RFWP_Cache::getTabletCache();
				$cacheTimeoutDesktop = RFWP_Cache::getDesktopCache();
				if (!empty($GLOBALS['dev_mode'])) {
					$cacheTimeoutMobile = 0;
					$cacheTimeoutTablet = 0;
					$cacheTimeoutDesktop = 0;
				}

				if (empty($cacheTimeoutDesktop)||empty($cacheTimeoutTablet)||empty($cacheTimeoutMobile)) {
					$cacheTimeout = RFWP_Cache::getCacheTimeout();

					if (!empty($GLOBALS['dev_mode'])) {
						$cacheTimeout = 0;
					}

					if (empty($cacheTimeout)) {
//					add_action('wp_enqueue_scripts', 'RFWP_syncFunctionAdd2', 11);
						add_action($insertPlace, 'RFWP_syncFunctionAdd21', 10);

					}
				}
				if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
					RFWP_WorkProgressLog(false,'js_add end');
				}
			}
		}
		/********** End of Autosync and JS text edit *************************************************************************/
	}
	/** End of Functions zone  *************************************************************************************************************************************************************/
    /***************** Clean content selector cache **************/
    if (!empty($_POST['content_selector'])) {
	    delete_transient('gatherContentContainerLong');
    }
    /***************** End of clean content selector cache **************/
    // @codingStandardsIgnoreStart
	$tableForCurrentPluginChecker = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', "{$wpPrefix}realbig_plugin_settings"));   //settings for block table checking
	$tableForToken                = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', "{$wpPrefix}realbig_settings"));      //settings for token and other
	$tableForTurboRssAds          = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', "{$wpPrefix}realbig_turbo_ads"));      //settings for ads in turbo RSS
	$tableForAmpAds               = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', "{$wpPrefix}realbig_amp_ads"));      //settings for ads in AMP
    // @codingStandardsIgnoreEnd

	if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
		RFWP_WorkProgressLog(false,'tables check');
	}

	if (empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON))) {
	    if ((!empty($curUserCan)&&!empty($_POST['statusRefresher']))||empty($tableForToken)||empty($tableForCurrentPluginChecker)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	        $wpdb->query($wpdb->prepare("DELETE FROM `{$wpPrefix}posts` WHERE post_type IN (%s, %s, %s, %s, %s) AND post_author = 0",
                "rb_block_mobile", "rb_block_desktop", "rb_block_mobile_new", "rb_block_tablet_new", "rb_block_desktop_new"));
	        RFWP_Cache::deleteCaches();
		    delete_option('realbig_status_gatherer_version');

		    if (empty($GLOBALS['wp_rewrite'])) {
			    $GLOBALS['wp_rewrite'] = new WP_Rewrite();
		    }
		    $oldShortcodes = get_posts(['post_type' => 'rb_shortcodes','numberposts' => 100]);
		    if (!empty($oldShortcodes)) {
			    foreach ($oldShortcodes AS $k => $item) {
				    wp_delete_post($item->ID);
			    }
			    unset($k, $item);
		    }

		    $messageFLog = 'clear cached ads';
            RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
	    }
    }
    RFWP_Utils::getVersion();
    if (!isset($lastSuccessVersionGatherer)||!isset($statusGatherer)) {
	    if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
		    RFWP_WorkProgressLog(false,'gather some statuses from options');
	    }
	    $lastSuccessVersionGatherer = get_option('realbig_status_gatherer_version');
	    $statusGatherer             = RFWP_statusGathererConstructor(true);
    }
	/***************** updater code ***************************************************************************************/

	/****************** end of updater code *******************************************************************************/
	/********** checking and creating tables ******************************************************************************/
	if ((!empty($lastSuccessVersionGatherer)&&$lastSuccessVersionGatherer != $GLOBALS['realbigForWP_version'])||empty($lastSuccessVersionGatherer)) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query($wpdb->prepare("DELETE FROM `{$wpPrefix}posts` WHERE post_type IN (%s, %s, %s, %s, %s) AND post_author = 0",
             "rb_block_mobile", "rb_block_desktop", "rb_block_mobile_new", "rb_block_tablet_new", "rb_block_desktop_new"));
        RFWP_Cache::deleteCaches();

		$messageFLog = 'clear cached ads';
        RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);

		if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
			RFWP_WorkProgressLog(false,'old cache clean');
		}
	}

	if ($statusGatherer['realbig_plugin_settings_table'] == false || $statusGatherer['realbig_settings_table'] == false ||
        $statusGatherer['realbig_turbo_ads_table'] == false || $lastSuccessVersionGatherer != $GLOBALS['realbigForWP_version']) {
		if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
			RFWP_WorkProgressLog(false,'create tables begin');
		}
		$statusGatherer = RFWP_dbTablesCreateFunction($tableForCurrentPluginChecker, $tableForToken, $tableForTurboRssAds, $tableForAmpAds, $wpPrefix, $statusGatherer);
		if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
			RFWP_WorkProgressLog(false,'create tables end');
		}
	}
	if ($statusGatherer['realbig_plugin_settings_table'] == true && ($statusGatherer['realbig_plugin_settings_columns'] == false || $lastSuccessVersionGatherer != $GLOBALS['realbigForWP_version'])) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$colCheck = $wpdb->get_col("SHOW COLUMNS FROM `{$wpPrefix}realbig_plugin_settings`");
		if (!empty($colCheck)) {
			$statusGatherer = RFWP_wpRealbigPluginSettingsColomnUpdateFunction($wpPrefix, $colCheck, $statusGatherer);
			if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
				RFWP_WorkProgressLog(false,'db table column update');
			}
		} else {
			$statusGatherer['realbig_plugin_settings_columns'] = false;
		}
	}
	/********** end of checking and creating tables ***********************************************************************/
	/********** token gathering and adding "timeUpdate" field in wp_realbig_settings **************************************/
	if (empty($GLOBALS['token'])||(!empty($GLOBALS['token'])&&$GLOBALS['token']=='no token')) {
		RFWP_tokenChecking($wpPrefix);
		if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
			RFWP_WorkProgressLog(false,'token checking');
		}
	}

    // @codingStandardsIgnoreStart
	$unmarkSuccessfulUpdate      = $wpdb->get_var($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` " .
        "WHERE optionName = %s", "successUpdateMark"));
	$jsAutoSynchronizationStatus = $wpdb->get_var($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` " .
        "WHERE optionName = %s", "jsAutoSyncFails"));
    // @codingStandardsIgnoreEnd

	if ($statusGatherer['realbig_plugin_settings_table'] == true && ($statusGatherer['element_column_values'] == false || $lastSuccessVersionGatherer != $GLOBALS['realbigForWP_version'])) {
		/** enumUpdate */
		$statusGatherer = RFWP_updateElementEnumValuesFunction($wpPrefix, $statusGatherer);
		if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
			RFWP_WorkProgressLog(false,'enum values updated');
		}
		/** enumUpdateEnd */
	}
	if (!empty($statusGatherer)) {
		if (!in_array(false, $statusGatherer)) {
			if (!empty($lastSuccessVersionGatherer)) {
				update_option('realbig_status_gatherer_version', $GLOBALS['realbigForWP_version'], 'no');
			} else {
				add_option('realbig_status_gatherer_version', $GLOBALS['realbigForWP_version'], '', 'no');
			}
		}
		$statusGathererJson = wp_json_encode($statusGatherer);
		if (!empty($statusGatherer['update_status_gatherer']) && $statusGatherer['update_status_gatherer'] == true) {
			update_option('realbig_status_gatherer', $statusGathererJson, 'no');
		} else {
			add_option('realbig_status_gatherer', $statusGathererJson, '', 'no');
		}
	}
	/********** end of token gathering and adding "timeUpdate" field in wp_realbig_settings *******************************/
	/********** checking requested page for excluding *********************************************************************/
    try {
        if (empty($GLOBALS['excludedPagesChecked'])&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))) {
	        if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
		        RFWP_WorkProgressLog(false,'excluded page check begin');
	        }
	        $excludedPage = false;
            $mainPageStatus = 0;

	        $usedUrl = '';
	        $usedUrl2 = '';
	        if (!empty($_SERVER["REDIRECT_URL"])) {
                $usedUrl = $_SERVER["REDIRECT_URL"];
            }
            if (!empty($_SERVER["REQUEST_URI"])) {
                $usedUrl2 = $_SERVER["REQUEST_URI"];
            }

            $punycode = new RFWP_Punycode();

            $usedUrl1[0] = urldecode($_SERVER["HTTP_HOST"].$usedUrl);
            $usedUrl1[1] = urldecode($_SERVER["HTTP_HOST"].$usedUrl2);
            $usedUrl1[2] = urldecode($punycode->decode($_SERVER["HTTP_HOST"]).$usedUrl);
            $usedUrl1[3] = urldecode($punycode->decode($_SERVER["HTTP_HOST"]).$usedUrl2);

            /** Test zone *********/
            /** End of test zone **/

            if (is_admin()) {
                $excludedPage = true;
            } elseif (!empty($usedUrl)||!empty($usedUrl2)) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $pageChecksDb = $wpdb->get_results($wpdb->prepare("SELECT optionValue, optionName FROM `{$wpPrefix}realbig_settings` " .
                    "WHERE optionName IN (%s,%s,%s)", "excludedMainPage", "excludedPages", "excludedPageTypes"), ARRAY_A);
                $pageChecks = [];
                foreach ($pageChecksDb AS $k => $item) {
                    $pageChecks[$item['optionName']] = $item['optionValue'];
                }
                $GLOBALS['pageChecks'] = $pageChecks;

                $homeStatus = false;
	            $getHomeUrl = get_home_url();
	            $getHomeUrl = preg_replace('~^http[s]*?\:\/\/~', '', $getHomeUrl);

	            preg_match_all("~(\/|\\\)([^\/^\\\]+)~", $getHomeUrl, $m);

                foreach ($usedUrl1 AS $usedUrl) {
                    if (!empty($usedUrl)&&!empty($m)) {
                        if ($usedUrl=="/"||$usedUrl==$getHomeUrl."/") {
                            $homeStatus = true;
                            break;
                        } else {
                            foreach ($m[0] AS $item) {
                                if ($usedUrl==$item."/") {
                                    $homeStatus = true;
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($homeStatus==true) {
                    if (isset($pageChecks['excludedMainPage'])) {
                        if ($pageChecks['excludedMainPage'] == 1) {
                            $mainPageStatus = 1;
                        } elseif ($pageChecks['excludedMainPage'] == 0) {
                            $mainPageStatus = 2;
                        }
                    }
                }

                if ($mainPageStatus == 1) {
                    $excludedPage = true;
                } elseif ($mainPageStatus == 0) {
                    if (!empty($pageChecks['excludedPages'])) {
                        $excludedDelimiter = 0;
                        $maxCountDelimiter = 0;
                        $excludedPagesCheckArray[1] = explode(",", $pageChecks['excludedPages']);
                        $excludedPagesCheckArray[2] = explode("\n", $pageChecks['excludedPages']);
                        $excludedPagesCheckArray[3] = explode(";", $pageChecks['excludedPages']);
                        $excludedPagesCheckArray[4] = explode(" ", $pageChecks['excludedPages']);

                        foreach ($excludedPagesCheckArray AS $k => $item) {
                            if (count($item) > $maxCountDelimiter) {
                                $maxCountDelimiter = count($item);
                                $excludedDelimiter = $k;
                            }
                        }
                        if ($excludedDelimiter > 0) {
                            $excludedPagesCheckArray = $excludedPagesCheckArray[$excludedDelimiter];
                        } else {
                            $excludedPagesCheckArray = $pageChecks['excludedPages'];
                        }

                        if (!empty($excludedPagesCheckArray)) {
                            foreach ($excludedPagesCheckArray AS $item) {
                                $item = trim($item);
                                $item = trim($item, ",;\n /");
                                $item = urldecode($item);
                                $item1 = preg_replace('~\\\~','\/', $item);
                                $item2 = preg_replace('~\/~','\\', $item);

                                if (!empty($item)) {
                                    $m = -1;
                                    foreach ($usedUrl1 AS $usedUrl) {
                                        $m1 = strpos($usedUrl, $item1);
                                        if (is_integer($m1)&&$m1 > -1) {
                                            $excludedPage = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $GLOBALS['excludedPagesChecked'] = true;
	        if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
		        RFWP_WorkProgressLog(false,'exc excluded check end');
	        }
        }
    } catch (Exception $excludedE) {
        $excludedPage = false;
    }
	/********** end of checking requested page for excluding **************************************************************/
	/********** new working system ****************************************************************************************/
    if (isset($excludedPage)&&$excludedPage==false&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))) {
	    add_shortcode('test_sc_oval', 'test_sc_oval_exec');
	    add_action('wp_head', 'RFWP_blocks_in_head_add', 101);
	    add_action('wp_head', 'RFWP_launch_without_content', 1001);
    }
	/********** end of new working system *********************************************************************************/
	/********** autosync and JS text edit *********************************************************************************/
	if (empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))) {
		add_action('wp_ajax_saveAdBlocks', 'saveAdBlocks');
		add_action('wp_ajax_nopriv_saveAdBlocks', 'saveAdBlocks');
		add_action('wp_ajax_setLongCache', 'setLongCache');
		add_action('wp_ajax_nopriv_setLongCache', 'setLongCache');

		$gatherContentTimeoutLong = get_transient(RFWP_Variables::GATHER_CONTENT_LONG);
		$gatherContentTimeoutShort = get_transient(RFWP_Variables::GATHER_CONTENT_SHORT);
		if (empty($gatherContentTimeoutLong)&&empty($gatherContentTimeoutShort)) {
//		        set_transient('gatherContentContainerShort', true, 60);
			add_action('wp_ajax_RFWP_saveContentContainer', 'RFWP_saveContentContainer');
			add_action('wp_ajax_nopriv_RFWP_saveContentContainer', 'RFWP_saveContentContainer');
		}
    }
	$lastSyncTimeTransient = RFWP_Cache::getAttemptCache();
	$activeSyncTransient = RFWP_Cache::getProcessCache();
	if (!empty($GLOBALS['token'])&&$GLOBALS['token']!='no token'&&empty($activeSyncTransient)&&empty($lastSyncTimeTransient)) {
		$nextSchedulerCheck = wp_next_scheduled('rb_cron_hook');
		if (empty($nextSchedulerCheck)) {
			RFWP_cronAutoGatheringLaunch();
		} elseif (!empty(apply_filters('wp_doing_cron', defined('DOING_CRON')&&DOING_CRON))) {
			RFWP_cronAutoSyncDelete();
		}
	}
	/** Cron check */
	if (!empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))) {
		RFWP_cronCheckLog('cron passed in main');
		if (!empty($GLOBALS['token'])) {
			RFWP_cronCheckLog('token: '.$GLOBALS['token'].';');
		}
		RFWP_cronCheckLog('sync process: '.$activeSyncTransient.';');
		RFWP_cronCheckLog('cron passed in main');
	}
	/** End of cron check */
	if (!empty($GLOBALS['token'])&&$GLOBALS['token']!='no token'&&empty($activeSyncTransient)&&empty($lastSyncTimeTransient)&&
            !empty(apply_filters('wp_doing_cron', defined('DOING_CRON')&&DOING_CRON))) {
        RFWP_cronCheckLog('cron going to sync');
        RFWP_autoSync();
	}
	/********** end autosync and JS text edit *****************************************************************************/
	/********** adding AD code in head area *******************************************************************************/
	// new
	if (!is_admin()&&empty(apply_filters('wp_doing_cron', defined('DOING_CRON')&&DOING_CRON))) {
		add_action('wp_head', 'RFWP_block_classes_add', 0);

		if (!empty($GLOBALS['rb_variables']['localRotatorUrl'])&&!empty($GLOBALS['rb_variables']['rotator'])&&empty($GLOBALS['rb_variables']['localRotatorToHead'])&&empty($GLOBALS['rb_variables']['adWithStatic'])) {
            $GLOBALS['rb_variables']['localRotatorToHead'] = true;
            add_action('wp_head', 'RFWP_rotatorToHeaderAdd', 0);
        }

        add_action('wp_head', 'RFWP_AD_header_add', 0);
		$separatedStatuses = [];
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$statuses = $wpdb->get_results($wpdb->prepare("SELECT optionName, optionValue FROM `{$wpPrefix}realbig_settings` " .
            "WHERE optionName IN (%s,%s,%s)", "pushUniversalCode", "pushUniversalStatus", "pushUniversalDomain"), ARRAY_A);
		if (!empty($statuses)) {
		    foreach ($statuses AS $k => $item) {
			    $separatedStatuses[$item['optionName']] = $item['optionValue'];
            }
			if (!empty($separatedStatuses)&&!empty($separatedStatuses['pushUniversalCode'])&&isset($separatedStatuses['pushUniversalStatus'])&&$separatedStatuses['pushUniversalStatus']==1) {
				add_action('wp_head', 'RFWP_push_universal_head_add', 0);
				$GLOBALS['rb_push']['universalCode'] = $separatedStatuses['pushUniversalCode'];
				if (empty($separatedStatuses['pushUniversalDomain'])) {
					$GLOBALS['rb_push']['universalDomain'] = 'truenat.bid';
				} else {
					$GLOBALS['rb_push']['universalDomain'] = $separatedStatuses['pushUniversalDomain'];
				}
			}
		}
		add_action('wp_head', 'RFWP_inserts_head_add', 0);
		if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
			RFWP_WorkProgressLog(false,'all inserts in header end');
		}
	}
	/********** end of adding AD code in head area ************************************************************************/
	/************* blocks for text ****************************************************************************************/
	if (empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&!is_admin()) {
        if (empty($excludedPage)) {
	        add_filter('the_content', 'RFWP_adBlocksToContentInsertingFunction', 5000);
        }

//		RFWP_addContentContainer();

		//	insertings body add
		RFWP_js_add();
		add_filter('the_content', 'RFWP_insertingsToContentAddingFunction', 5001);

//		add_shortcode('test_sc_oval', 'test_sc_oval_exec');
//		add_filter('the_content', 'RFWP_shortCodesAdd', 4999);
		if (!is_admin()&&empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))&&empty(apply_filters('wp_doing_ajax',defined('DOING_AJAX')&&DOING_AJAX))) {
			RFWP_WorkProgressLog(false,'add content filter end');
		}
	}
	/************* end blocks for text ************************************************************************************/
	/*********** begin of token input area ********************************************************************************/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if (is_admin()) {
		include_once (plugin_dir_path(__FILE__)."RFWP_AdminPage.php");
		add_action('admin_menu', 'RFWP_AdminPage::settingsMenuCreate');
        RFWP_AdminPage::clickButtons();
	}
	/************ end of token input area *********************************************************************************/
	add_action( 'after_setup_theme', 'RFWP_saveThemeThumbnailSizes', 5000);
}
catch (Exception $ex)
{
    try {
	    global $wpdb;

	    $messageFLog = 'Deactivation error: '.$ex->getMessage().'; line: '.$ex->getLine().';';
	    if (!empty($_POST)) {
	        if (!empty($_POST['action'])) {
                $messageFLog .= ' request type: '.$_POST['action'].';';
            }
        }
	    if (!empty($_GET)) {
            if (!empty($_GET['doing_wp_cron'])) {
                $messageFLog .= ' request type: cron;';
            }
        }

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
catch (Error $ex)
{
	try {
        global $wpdb;

        $messageFLog = 'Deactivation error: '.$ex->getMessage().'; line: '.$ex->getLine().';';
        if (!empty($_POST)) {
            if (!empty($_POST['action'])) {
                $messageFLog .= ' request type: '.$_POST['action'].';';
            }
        }
        if (!empty($_GET)) {
            if (!empty($_GET['doing_wp_cron'])) {
                $messageFLog .= ' request type: cron;';
            }
        }

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