<?php

if (!defined("ABSPATH")) {exit;}

if (!class_exists('RFWP_Cache')) {
    class RFWP_Cache {
        const MOBILE_CACHE = "rb_mobile_cache_timeout";
        const TABLET_CACHE = "rb_tablet_cache_timeout";
        const DESKTOP_CACHE = "rb_desktop_cache_timeout";

        const CACHE_TIMEOUT = "rb_cache_timeout";
        const ACTIVE_CACHE = "rb_active_cache";
        const LONG_CACHE = "rb_longCacheDeploy";

        const ATTEMPT_CACHE = "rb_syncAttempt_cache";
        const PROCESS_CACHE = "rb_syncProcess_cache";

        public static function getMobileCache() {
            return self::getCache(self::MOBILE_CACHE);
        }
        public static function setMobileCache() {
            self::setCache(self::MOBILE_CACHE, 60*60);
        }

        public static function getTabletCache() {
            return self::getCache(self::TABLET_CACHE);
        }
        public static function setTabletCache() {
            self::setCache(self::TABLET_CACHE, 60*60);
        }

        public static function getDesktopCache() {
            return self::getCache(self::DESKTOP_CACHE);
        }
        public static function setDesktopCache() {
            self::setCache(self::DESKTOP_CACHE, 60*60);
        }

        public static function getCacheTimeout() {
            return self::getCache(self::CACHE_TIMEOUT);
        }
        public static function setCacheTimeout() {
            self::setCache(self::CACHE_TIMEOUT, 60);
        }

        public static function getActiveCache() {
            return self::getCache(self::ACTIVE_CACHE);
        }
        public static function setActiveCache() {
            self::setCache(self::ACTIVE_CACHE, 5);
        }
        public static function deleteActiveCache() {
            self::deleteCache(self::ACTIVE_CACHE);
        }

        public static function getProcessCache() {
            return self::getCache(self::PROCESS_CACHE);
        }
        public static function setProcessCache() {
            self::setCache(self::PROCESS_CACHE, 30);
        }
        public static function deleteProcessCache() {
            self::deleteCache(self::PROCESS_CACHE);
        }

        public static function getAttemptCache() {
            return self::getCache(self::ATTEMPT_CACHE);
        }
        public static function setAttemptCache() {
            $period = RFWP_getPeriodSync();
            self::setCache(self::ATTEMPT_CACHE, $period);
        }
        public static function deleteAttemptCache() {
            self::deleteCache(self::ATTEMPT_CACHE);
        }

        public static function getLongCache() {
            if (!empty($GLOBALS['dev_mode'])) {
                $longCache = false;
                $GLOBALS['rb_longCache'] = $longCache;
            } else {
                if (!isset($GLOBALS['rb_longCache'])) {
                    $longCache = self::getCache(self::LONG_CACHE);
                    $GLOBALS['rb_longCache'] = $longCache;
                } else {
                    $longCache = $GLOBALS['rb_longCache'];
                }
            }
            return $longCache;
        }

        public static function setLongCache() {
            self::setCache(self::LONG_CACHE, 300);
        }

        public static function deleteCaches() {
            self::deleteCache(self::CACHE_TIMEOUT);
            self::deleteCache(self::LONG_CACHE);
            self::deleteDeviceCaches();
        }

        public static function deleteDeviceCaches() {
            self::deleteCache(self::MOBILE_CACHE);
            self::deleteCache(self::TABLET_CACHE);
            self::deleteCache(self::DESKTOP_CACHE);
        }

        public static function clearCaches() {
            self::deleteCaches();
            global $wpdb;
            global $wpPrefix;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
            $wpdb->query($wpdb->prepare("DELETE FROM `{$wpPrefix}posts` WHERE post_type IN (%s, %s, %s)",
                "rb_block_desktop_new", "rb_block_tablet_new", "rb_block_mobile_new"));
        }

        private static function getCache($cache) {
            $item = get_transient($cache);

            if (!empty($item) && time() > $item) {
                self::deleteAttemptCache();
                $item = false;
            }

            return $item;

        }
        private static function setCache($cache, $period) {
            set_transient($cache, time()+$period, $period);
        }
        private static function deleteCache($cache) {
            delete_transient($cache);
        }
    }
}