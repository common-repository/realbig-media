<?php

if (!defined("ABSPATH")) { exit;}

try {
    global $wpdb;
    global $wpPrefix;
	$dev_mode = $GLOBALS['dev_mode'];

	$returnData = [];
    $returnData['errors'] = [];
    $errorsGather = '';

    $checkCacheTimeoutMobile = RFWP_Cache::getMobileCache();
    $checkCacheTimeoutTablet = RFWP_Cache::getTabletCache();
    $checkCacheTimeoutDesktop = RFWP_Cache::getDesktopCache();

    if (!empty($checkCacheTimeoutMobile)&&!empty($checkCacheTimeoutTablet)&&!empty($checkCacheTimeoutDesktop)) {
        return true;
    }

	$stopIt = false;
    while (empty($stopIt)) {
	    $checkCacheTimeout = RFWP_Cache::getCacheTimeout();
	    if (!empty($checkCacheTimeout)) {
		    return true;
	    }
	    $checkActiveCaching = RFWP_Cache::getActiveCache();
	    if (!empty($checkActiveCaching)) {
		    sleep(6);
	    } else {
            RFWP_Cache::setActiveCache();
	        $stopIt = true;
        }
    }

    $data = '';
    if (!empty($_POST['_csrf']) && wp_verify_nonce($_POST['_csrf'], RFWP_Variables::CSRF_USER_JS_ACTION) && !empty($_POST['data'])) {
        $data = $_POST['data'];

	    $data = preg_replace("~\\\'~", "'", $data);
	    $data = preg_replace('~\\\"~', '"', $data);

	    $savingResult = RFWP_savingCodeForCache($data);
    }
} catch (Exception $ex) {
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

        RFWP_Utils::saveToRbSettings('realbigForWP: ' . $ex->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

//	include_once ( dirname(__FILE__)."/../../../wp-admin/includes/plugin.php" );
	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($ex); ?></div><?php
} catch (Error $er) {
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

        RFWP_Utils::saveToRbSettings('realbigForWP: ' . $er->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

//	include_once ( dirname(__FILE__)."/../../../wp-admin/includes/plugin.php" );
	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($er); ?></div><?php
}