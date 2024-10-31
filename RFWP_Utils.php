<?php

if (!defined("ABSPATH")) {exit;}

if (!class_exists('RFWP_Utils')) {
    class RFWP_Utils {
        const PATH_PLUGIN = __DIR__ . '/realbigForWP.php';

        public static function getVersion() {
            if (!isset($GLOBALS['realbigForWP_version'])) {
                $pluginData = get_plugin_data(self::PATH_PLUGIN);
                if (!empty($pluginData['Version'])) {
                    $GLOBALS['realbigForWP_version'] = $pluginData['Version'];
                } else {
                    $GLOBALS['realbigForWP_version'] = null;
                }
            }

            return $GLOBALS['realbigForWP_version'];
        }

        public static function getName() {
            if (!isset($GLOBALS['realbigForWP_name'])) {
                $pluginData = get_plugin_data(self::PATH_PLUGIN);
                if (!empty($pluginData['Name'])) {
                    $GLOBALS['realbigForWP_name'] = $pluginData['Name'];
                } else {
                    $GLOBALS['realbigForWP_name'] = 'Realbig Media';
                }
            }

            return $GLOBALS['realbigForWP_name'];
        }

        public static function getYesOrNo($i) {
            $array = ["Нет", "Да"];

            return !empty($array[$i]) ? $array[$i] : $i;
        }

        public static function getEnableOrDisable($i) {
            $array = ["enable" => "Включить", "disable" => "Выключить"];

            return !empty($array[$i]) ? $array[$i] : $i;
        }

        public static function saveToRbSettings($value, $optionName) {
            try {
                global $wpdb;
                $wpPrefix = RFWP_getWpPrefix();

                // @codingStandardsIgnoreStart
                $getOption = $wpdb->query($wpdb->prepare("SELECT id FROM `{$wpPrefix}realbig_settings` WHERE optionName = %s", $optionName));
                if (empty($getOption)) {
                    $res = $wpdb->insert($wpPrefix.'realbig_settings', ['optionName' => $optionName, 'optionValue' => $value]);
                } else {
                    $res = $wpdb->update($wpPrefix.'realbig_settings', ['optionValue' => $value], ['optionName' => $optionName]);
                }
                // @codingStandardsIgnoreEnd

                return $res;
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

        public static function getFromRbSettings($optionName) {
            global $wpdb;
            $wpPrefix = RFWP_getWpPrefix();
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $getOption = $wpdb->get_var($wpdb->prepare("SELECT optionValue FROM `{$wpPrefix}realbig_settings` WHERE optionName = %s",
                $optionName));

            return $getOption;
        }
    }
}