<?php

/** Kill rb connection emulation */
// 1 - ok connection; 2 - error connection;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$kill_rb_db = $wpdb->get_results($wpdb->prepare("SELECT id,optionValue FROM `{$wpPrefix}realbig_settings` WHERE optionName = %s",
    "kill_rb"), ARRAY_A);
if (empty(apply_filters('wp_doing_cron', defined('DOING_CRON') && DOING_CRON)) && !empty(is_admin())
    && wp_get_raw_referer() && !wp_get_referer()) {
	if (!empty($curUserCan) && !empty($_POST['saveTokenButton']) &&
            !empty($_POST["_csrf"]) && wp_verify_nonce($_POST["_csrf"], RFWP_Variables::CSRF_ACTION)) {
		if (!empty($_POST['kill_rb'])) {
			$saveVal = 2;
		} else {
			$saveVal = 1;
		}
        // @codingStandardsIgnoreStart
		if (!empty($kill_rb_db)&&count($kill_rb_db) > 0) {
			$wpdb->update($wpPrefix.'realbig_settings',['optionValue'=>$saveVal],['optionName'=>'kill_rb']);
		} else {
			$wpdb->insert($wpPrefix.'realbig_settings',['optionValue'=>$saveVal,'optionName'=>'kill_rb']);
		}
        // @codingStandardsIgnoreEnd
		$kill_rb_db = $saveVal;
	} else {
		if (!empty($kill_rb_db)&&count($kill_rb_db) > 0) {
			$kill_rb_db = $kill_rb_db[0]['optionValue'];
		} else {
			$kill_rb_db = 1;
		}
	}
} else {
	if (!empty($kill_rb_db)&&count($kill_rb_db) > 0) {
		$kill_rb_db = $kill_rb_db[0]['optionValue'];
	} else {
		$kill_rb_db = 1;
	}
}
$kill_rb = $kill_rb_db;
if (!isset($kill_rb)) {
	$kill_rb = 0;
}
$GLOBALS['kill_rb'] = $kill_rb;
/** End of kill rb connection emulation */
/** Check IP */
if (!empty($curUserCan)) {
	$testServerName = 'placeholder';
	$testHttpOrigin = 'placeholder';
	$testHttpHost = 'placeholder';

	if (!empty($_SERVER['SERVER_NAME'])) {
		$testServerName = $_SERVER['SERVER_NAME'];
	}
	if (!empty($_SERVER['HTTP_ORIGIN'])) {
		$testHttpOrigin = $_SERVER['HTTP_ORIGIN'];
	}
	if (!empty($_SERVER['HTTP_HOST'])) {
		$testHttpHost = $_SERVER['HTTP_HOST'];
	}
	/*?><script>console.log('SERVER_NAME:<?php echo $testServerName.';';?>');console.log('HTTP_ORIGIN:<?php echo $testHttpOrigin.';';?>');console.log('HTTP_HOST:<?php echo $testHttpHost.';';?>')</script><?php*/
}
/** End of Check IP */
/** Check in header inserting */
if (!function_exists('RFWP_checkHeader')) {
	function RFWP_checkHeader($content) {
//        $content .= '<!-- RFWP inserting detected -->';
		$content .= '<script>console.log("header passed 1");</script>';

		return $content;
	}
}
/** End of Check in header inserting */





