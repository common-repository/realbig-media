<?php

if (!defined("ABSPATH")) {exit;}

if (!class_exists('RFWP_AdUtils')) {
    class RFWP_AdUtils {
        public static function getSettingsType($index)
        {
            $array = [1 => 'Одиночный', 'Повторяющийся', 'По селектору', 'В конце', 'В середине', 'В процентах %', 'В символах'];
            return !empty($array[$index]) ? $array[$index] : $index;
        }

        public static function getTurboSettingsType($index)
        {
            $array = ['single' => 'Одиночный', 'begin' => 'В начале', 'middle' => 'В середине', 'end' => 'В конце'];
            return !empty($array[$index]) ? $array[$index] : $index;
        }

        public static function getTurboAdNetwork($network) {
            $array = ['rsya' => 'РСЯ', 'adfox' => 'АдФокс'];

            return !empty($array[$network]) ? $array[$network] : $network;
        }
    }
}