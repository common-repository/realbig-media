<?php

if (!function_exists('RFWP_syncNow')) {
	function RFWP_syncNow(WP_REST_Request $request) {
		$result = [];
        global $rb_periodSync;
        $rb_periodSync = 0.2;

        RFWP_Cache::deleteAttemptCache();
		RFWP_cronAutoGatheringLaunch();
		$result['result'] = 'timeout cleared';

		return $result;
	}
}

if (!function_exists('RFWP_syncNowPermission')) {
	function RFWP_syncNowPermission($request) {
		$justUsed = get_transient(RFWP_Variables::CUSTOM_SYNC);
		$expiration = 5;
		if (empty($justUsed)) {
			set_transient(RFWP_Variables::CUSTOM_SYNC, true, $expiration);
			return true;
		} else {
			return false;
		}
	}
}

add_action('rest_api_init', function () {
	register_rest_route('myplugin/v1', '/rb_4td6_resync/', array(
//		'methods'  => 'POST',
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'RFWP_syncNow',
		'permission_callback' => 'RFWP_syncNowPermission'
	));
});