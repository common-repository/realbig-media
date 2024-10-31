<?php

if (!defined("ABSPATH")) {exit;}

try {
    if (!class_exists('RFWP_CachePlugins')) {
        class RFWP_CachePlugins {
            private static $pluginList;

            public static function cacheClear() {
                $allowCacheClear = get_option('rb_cacheClearAllow');
                if (!empty($allowCacheClear)&&$allowCacheClear=='enabled') {
                    self::$pluginList = self::getCachePluginList();
                    if (!empty(self::$pluginList)) {
                        foreach (self::$pluginList as $item) {
                            try {
                                self::$item();
                            } catch (Exception $ex) {
                                $messageFLog = 'Some error in RFWP_CachePlugins->cacheClear : '.$ex->getMessage().';';
                                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
                            } catch (Error $er) {
                                $messageFLog = 'Some error in RFWP_CachePlugins->cacheClear : '.$er->getMessage().';';
                                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
                            }
                        }
                        unset($item);
                    }
                }
            }

            private static function getCachePluginList() {
                $list = [
                    'autoptimizeCacheClear',
                    'wpSuperCacheCacheClear',
                    'wpFastestCacheCacheClear',
                    'w3TotalCacheCacheClear',
                    'liteSpeedCacheCacheClear',
                ];
                return $list;
            }

            /** Function for cache plugins */
            public static function autoptimizeCacheClearExecute() {
                if (class_exists('autoptimizeCache')&&method_exists(autoptimizeCache::class, 'clearall')) {
                    autoptimizeCache::clearall();
                    if (empty(apply_filters('wp_doing_cron',defined('DOING_CRON')&&DOING_CRON))) {
                        header("Refresh:0");  # Refresh the page so that autoptimize can create new cache files and it does breaks the page after clearall.
                    }
                }
            }

            private static function autoptimizeCacheClear() {
	            if (class_exists('autoptimizeCache')&&method_exists(autoptimizeCache::class, 'clearall')) {
	                self::autoptimizeCacheClearExecute();
                } else{
		            add_action('plugins_loaded', array(get_called_class(), 'autoptimizeCacheClearExecute'));
	            }

                return true;
            }

            public static function wpSuperCacheCacheClearExecute() {
	            if (function_exists('wp_cache_clean_cache')) {
		            global $file_prefix;
		            wp_cache_clean_cache($file_prefix, true);
	            }
	            return true;
            }

            private static function wpSuperCacheCacheClear() {
	            if (function_exists('wp_cache_clean_cache')) {
	                self::wpSuperCacheCacheClearExecute();
                } else {
		            add_action('plugins_loaded', array(get_called_class(), 'wpSuperCacheCacheClearExecute'));
                }
	            return true;
            }

            public static function wpFastestCacheCacheClearExecute() {
                if (class_exists('WpFastestCache')&&method_exists(WpFastestCache::class, 'deleteCache')) {
                    $wpfc = new WpFastestCache();
                    $wpfc->deleteCache();
                }
            }

            private static function wpFastestCacheCacheClear() {
                if (class_exists('WpFastestCache')&&method_exists(WpFastestCache::class, 'deleteCache')) {
                    self::wpFastestCacheCacheClearExecute();
                } else {
	                add_action('plugins_loaded', array(get_called_class(), 'wpFastestCacheCacheClearExecute'));
                }

	            return true;
            }

            public static function w3TotalCacheCacheClearExecute() {
                if (function_exists('w3tc_flush_all')) {
                    w3tc_flush_all();
                }
            }

            private static function w3TotalCacheCacheClear() {
	            if (function_exists('w3tc_flush_all')) {
	                self::w3TotalCacheCacheClearExecute();
	            } else {
		            add_action('plugins_loaded', array(get_called_class(), 'w3TotalCacheCacheClearExecute'));
	            }

                return true;
            }

            public static function liteSpeedCacheCacheClearExecute() {
                do_action('litespeed_purge_all');
            }

            private static function liteSpeedCacheCacheClear() {
	            do_action('litespeed_purge_all');
                add_action('plugins_loaded', array(get_called_class(), 'liteSpeedCacheCacheClearExecute'));
                return true;
            }

            public static function checkCachePlugins() {
                $result = [];

                if (!empty(has_action('litespeed_purge_all'))) {
                    $result['liteSpeed'] = '<span style="color: #2dcb47">True</span>';
                } else {
                    $result['liteSpeed'] = '<span style="color: #ff1c1c">False</span>';
                }
                if (class_exists('WpFastestCache')&&method_exists(WpFastestCache::class, 'deleteCache')) {
                    $result['wpFastestCache'] = '<span style="color: #2dcb47">True</span>';
                } else {
                    $result['wpFastestCache'] = '<span style="color: #ff1c1c">False</span>';
                }
                if (class_exists('autoptimizeCache')&&method_exists(autoptimizeCache::class, 'clearall')) {
                    $result['autoptimize'] = '<span style="color: #2dcb47">True</span>';
                } else {
                    $result['autoptimize'] = '<span style="color: #ff1c1c">False</span>';
                }
                if (!empty(function_exists('wp_cache_clean_cache'))) {
                    $result['wpSuperCache'] = '<span style="color: #2dcb47">True</span>';
                } else {
                    $result['wpSuperCache'] = '<span style="color: #ff1c1c">False</span>';
                }
                if (!empty(function_exists('w3tc_flush_all'))) {
                    $result['w3TotalCache'] = '<span style="color: #2dcb47">True</span>';
                } else {
                    $result['w3TotalCache'] = '<span style="color: #ff1c1c">False</span>';
                }

                return $result;
            }
            /** End of Function for cache plugins */
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

        RFWP_Utils::saveToRbSettings('caches: ' . $ex->getMessage(), 'deactError');
	} catch (Exception $exIex) {} catch (Error $erIex) {}

	deactivate_plugins(plugin_basename(__FILE__));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($ex); ?></div><?php
}
catch (Error $ex) {
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

        RFWP_Utils::saveToRbSettings('caches: ' . $ex->getMessage(), 'deactError');
	} catch (Exception $exIex) {} catch (Error $erIex) {}

	deactivate_plugins(plugin_basename(__FILE__));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($ex); ?></div><?php
}