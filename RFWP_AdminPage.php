<?php

if (!defined("ABSPATH")) {exit;}

if (!class_exists('RFWP_AdminPage')) {
    class RFWP_AdminPage
    {
        public static function settingsMenuCreate() {
            global $wp_filesystem;
            $iconUrl = "";

            try {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
                $filePath = plugin_dir_path( __FILE__ ).'assets/images/realbig_plugin_standart.svg';
                if ( $wp_filesystem->exists( $filePath ) ) {
                    $iconUrl = $wp_filesystem->get_contents( $filePath );
                    $iconUrl = 'data:image/svg+xml;base64,' . base64_encode($iconUrl);
                }
            } catch (Exception $ex) {
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, 'Error Load Menu Icon: ' . $ex->getMessage());
            } catch (Error $ex) {
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, 'Error Load Menu Icon: ' . $ex->getMessage());
            }

            add_menu_page( 'Your code sending configuration', 'realBIG', 'administrator', "rfwp_admin_page", '\RFWP_AdminPage::tokenSync', $iconUrl);
            add_action('admin_init', 'RFWP_AdminPage::registerSettings');
        }

        public static function registerSettings() {
            register_setting('sending_zone', 'token_value_input');
            register_setting('sending_zone', 'token_value_send' );
        }

        public static function tokenSync() {
            if (!is_admin() || !current_user_can('activate_plugins'))
                return;

            global $wpdb;
            global $wpPrefix;
            global $curlResult;
            global $devMode;

            $res = [
                'devMode' => $devMode,
                'curlResult' => $curlResult,
                'cache' => [],
                'workProcess' => '',
                'cache_clear' => '',
                'killRbCheck' => '',
                'deacError' => '',
                'deacTime' => '',
                'enable_logs' => '',
                'rbSettings' => null,
                'turboOptions' => RFWP_generateTurboRssUrls(),
                'tab' => isset($_GET['tab']) ? $_GET['tab'] : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                "_csrf" => wp_create_nonce(RFWP_Variables::CSRF_ACTION),
            ];

            RFWP_initTestMode();
            RFWP_saveThemeThumbnailSizes();

            if (!empty($GLOBALS['dev_mode'])) {
                $res['killRbAvailable'] = true;
            } else {
                $res['killRbAvailable'] = false;
            }

            // @codingStandardsIgnoreStart
            $res['getBlocks'] = $wpdb->get_results("SELECT * FROM `{$wpPrefix}realbig_plugin_settings`", ARRAY_A);

            $cached = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_content, post_type FROM `{$wpPrefix}posts` " .
                "WHERE post_type IN (%s, %s, %s)", "rb_block_desktop_new", "rb_block_tablet_new", "rb_block_mobile_new"));
            // @codingStandardsIgnoreEnd
            $cacheKeys = ["rb_block_desktop_new" => "desktop", "rb_block_tablet_new" => "tablet", "rb_block_mobile_new" => "mobile"];
            if (!empty($cached)) {
                foreach ($cached as $cache) {
                    $type = isset($cacheKeys[$cache->post_type]) ? $cacheKeys[$cache->post_type] : $cache->post_type;

                    if (!isset($res['cache'][$cache->post_title][$type])) {
                        $res['cache'][$cache->post_title][$type] = $cache->post_content;
                    }
                }
            }

            try {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
                $res['rbSettings'] = $wpdb->get_results($wpdb->prepare("SELECT optionName, optionValue, timeUpdate " .
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "FROM `{$GLOBALS['wpPrefix']}realbig_settings` " .
                    'WHERE optionName IN (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)',
                    "deactError", "domain", "excludedMainPage", "excludedPages", "pushStatus", "excludedPageTypes",
                    "excludedIdAndClasses", "kill_rb", "pushUniversalStatus", "pushUniversalDomain", "statusFor404",
                    "blockDuplicate", "jsToHead", "obligatoryMargin", "tagsListForTextLength", "usedTaxonomies",
                    "enableLogs"), ARRAY_A);
//			$rbTransients = $wpdb->get_results('SELECT optionName, optionValue, timeUpdate FROM ' . $GLOBALS["wpPrefix"] . 'realbig_settings WHERE optionName IN ("deactError","domain","excludedMainPage","excludedPages","pushStatus","excludedPageTypes","kill_rb")', ARRAY_A);

                if (!empty($res['rbSettings'])) {
                    foreach ($res['rbSettings'] AS $k=>$item) {
                        if ($item['optionName']=='pushUniversalStatus') {
                            $res['pushStatus'] = $item["optionValue"];
                        } elseif ($item['optionName']=='pushUniversalDomain') {
                            $res['pushDomain'] = $item["optionValue"];
                        } elseif ($item['optionName']=='statusFor404') {
                            $res['statusFor404'] = $item["optionValue"] == 'show' ? 1 : 0;
                        } elseif ($item['optionName']=='deactError') {
                            $res['deacError'] = $item["optionValue"];
                            $res['deacTime'] = $item["timeUpdate"];
                        } elseif ($item['optionName']=='excludedPageTypes') {
                            if (!empty($item["optionValue"]) && $item['optionValue'] != 'nun') {
                                $res['excludedPageTypes'] = explode(',',$item["optionValue"]);
                            }
                        } elseif ($item['optionName']=='tagsListForTextLength') {
                            if (!empty($item["optionValue"]) && $item['optionValue'] != 'nun') {
                                $res['tagsListForTextLength'] = explode(';',$item["optionValue"]);
                            }
                        } elseif ($item['optionName']=='usedTaxonomies') {
                            $taxonomies = RFWP_getTaxonomies();
                            if (!empty($taxonomies)) {
                                $res['usedTaxonomies'] = [];
                                $usedTaxonomies = json_decode($item['optionValue'], JSON_UNESCAPED_UNICODE);
                                foreach ($usedTaxonomies as $type => $taxonomyType) {
                                    if (!empty($taxonomyType) && !empty($taxonomies[$type])) {
                                        foreach ($taxonomyType as $taxonomy) {
                                            if (!empty($taxonomies[$type][$taxonomy])) {
                                                $res['usedTaxonomies'][] = $taxonomies[$type][$taxonomy];
                                            }
                                        }
                                    }
                                }
                                $res['usedTaxonomies'] = implode('; ', $res['usedTaxonomies']);
                            }
                        } elseif ($item['optionName']=='kill_rb') {
                            if (!empty($GLOBALS['dev_mode'])) {
                                if (!empty($item["optionValue"])&&$item["optionValue"]==2) {
                                    $res['killRbCheck'] = 'checked';
                                }
                                if (!empty($item["optionValue"])) {
                                    $res['killRbAvailable'] = true;
                                }
                            }
                        } elseif ($item['optionName']=='enableLogs') {
                            if (!empty($item["optionValue"])&&$item["optionValue"]==1) {
                                $res['enable_logs'] = 'checked';
                            }
                        } else {
                            $res[$item['optionName']] = $item['optionValue'];
                        }
                    }
                }

                $res['cache_clear'] = get_option('rb_cacheClearAllow');
                if (!empty($res['cache_clear'])&&$res['cache_clear']=='enabled') {
                    $res['cache_clear'] = 'checked';
                } else {
                    $res['cache_clear'] = '';
                }
            } catch (Exception $e) {
                $res = [];
            }

            $GLOBALS['rb_adminPage_args'] = $res;
            load_template(__DIR__ . '/templates/adminPage.php');
        }

        public static function clickButtons() {
            if (empty($_POST["_csrf"]) || !wp_verify_nonce($_POST["_csrf"], RFWP_Variables::CSRF_ACTION))
                return;

            global $wpPrefix;

            if (!empty($_POST) && wp_get_raw_referer() && !wp_get_referer() &&
                    preg_replace('~^https?://~', '//', home_url() . wp_unslash( $_SERVER['REQUEST_URI'] )) ===
                        preg_replace('~^https?://~', '//', wp_get_raw_referer())) {
                if (!empty($_POST['clearLogs'])) {
                    RFWP_Logs::clearAllLogs();
                }
                else if (!empty($_POST['clearCache'])) {
                    RFWP_Cache::clearCaches();
                }

                /* manual sync */
                $updateLogs = false;
                if (!empty($_POST['enableLogsButton'])) {
                    RFWP_Utils::saveToRbSettings(!empty($_POST['enable_logs']) ? '1' : '0', "enableLogs");
                    $updateLogs = true;
                }
                if (!empty($_POST['saveTokenButton'])) {
                    if (!empty($_POST['cache_clear'])) {
                        update_option('rb_cacheClearAllow', 'enabled');
                    } else {
                        update_option('rb_cacheClearAllow', 'disabled');
                    }
                }
                if (!empty($_POST['tokenInput'])) {
                    $sanitized_token = sanitize_text_field($_POST['tokenInput']);
                    if (RFWP_tokenMDValidate($sanitized_token)==true) {
                        $sameTokenResult = false;
                        if (!isset($GLOBALS['RFWP_synchronize_vars'])) {
                            $GLOBALS['RFWP_synchronize_vars'] = [];
                            $GLOBALS['RFWP_synchronize_vars']['token'] = $sanitized_token;
                            $GLOBALS['RFWP_synchronize_vars']['sameTokenResult'] = $sameTokenResult;
                            $GLOBALS['RFWP_synchronize_vars']['type'] = 'manual';
                            $GLOBALS['RFWP_synchronize_vars']['updateLogs'] = $updateLogs;
                        }

                        RFWP_synchronizeLaunchAdd();
                        add_action('wp_loaded', 'RFWP_cronAutoGatheringLaunch');
                    } else {
                        $GLOBALS['tokenStatusMessage'] = 'Неверный формат токена';
                        $messageFLog = 'wrong token format';
                    }
                }
                /* end of manual sync */

                /* check ip */
                if (!empty($_POST['checkIp'])) {
                    $thisUrl = 'http://ifconfig.co/ip';
                    $response = wp_remote_get($thisUrl);
                    $curlResult = wp_remote_retrieve_body($response);
                    if (!empty($curlResult)) {
                        global $curlResult;
                        RFWP_Logs::saveLogs(RFWP_Logs::IP_LOG, PHP_EOL.$curlResult);
                    }
                }
                /* end of check ip */
            } else {
                if ($GLOBALS['token'] == 'no token') {
                    $GLOBALS['tokenStatusMessage'] = 'Введите токен';
                    $messageFLog = 'no token';
                }
            }

            RFWP_tokenTimeUpdateChecking($GLOBALS['token'], $wpPrefix);

            if (!empty($messageFLog)) {
                RFWP_Logs::saveLogs(RFWP_Logs::ERRORS_LOG, $messageFLog);
            }
        }
    }
}