<?php

if (!defined("ABSPATH")) {exit;}

if (!class_exists('RFWP_Logs')) {
    class RFWP_Logs {
        const AMP_LOG = 'amp.log';
        const CRON_LOG = 'cron.log';
        const ERRORS_LOG = 'errors.log';
        const IP_LOG = 'ip.log';
        const MODULES_LOG = 'modules.log';
        const RSS_LOG = 'rss.log';
        const WORK_PROCESS_LOG = 'process.log';

        const LOGS = [
            'AMP' => self::AMP_LOG,
            'Cron' => self::CRON_LOG,
            'Ip' => self::IP_LOG,
            'Modules' => self::MODULES_LOG,
            'Rss' => self::RSS_LOG,
            'Process' => self::WORK_PROCESS_LOG
        ];

        public static function saveLogs($fileName, $text, $useDateBefore = true) {
            global $rb_enableLogs;
            try {
                if (!empty($fileName) && in_array($fileName, self::LOGS)) {
                    if ($rb_enableLogs) {
                        $filePath = plugin_dir_path(__FILE__) . 'logs/' . $fileName;

                        clearstatcache();
                        if (!file_exists(dirname($filePath)))
                            wp_mkdir_p(dirname($filePath));

                        $message = PHP_EOL;
                        if (!empty($useDateBefore)) {
                            $message .= current_time('mysql');
                        }

                        error_log($message.': '.$text.PHP_EOL, 3, $filePath);
                    }
                }
            } catch (Exception $ex) {} catch (Error $er) {}
        }

        public static function clearLog($logFile) {
            $dir = plugin_dir_path(__FILE__) . 'logs/';
            if (in_array($logFile, self::LOGS) && file_exists($dir . $logFile)) {
                wp_delete_file($dir . $logFile);
            }
        }

        public static function clearAllLogs() {
            foreach (self::LOGS as $log) {
                self::clearLog($log);
            }
        }

        public static function initEnableLogs() {
            global $rb_enableLogs;

            if (!isset($rb_enableLogs)) {
                $rb_enableLogs = !empty(RFWP_Utils::getFromRbSettings('enableLogs'));
            }
        }
    }
}