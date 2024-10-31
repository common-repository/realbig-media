<?php

if (!defined("ABSPATH")) {exit;}

if (!class_exists('RFWP_Amp')) {
	class RFWP_Amp {
		public static function detectAmpPage () {
            add_action('template_redirect', array(get_called_class(), 'detectAmpPage_amp'));
		}

		public static function detectAmpPage_amp ($var1) {
			if (function_exists('amp_is_request')) {
			    if (!empty(amp_is_request())) {
			        if (!isset($GLOBALS['rfwp_is_amp'])) {
                        $GLOBALS['rfwp_is_amp'] = true;
                    }

			        $ampOptions = self::getAmpOption();
//				    if (!empty($ampOptions)&&!empty($ampOptions['status'])&&$ampOptions['status']=='enabled') {
				    if (!empty($ampOptions)&&isset($ampOptions['ampEnable'])&&intval($ampOptions['ampEnable'])==1) {
				    	global $wpdb;
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
					    $tableForAmpAds = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', "{$GLOBALS['wpPrefix']}realbig_amp_ads"));      //settings for ads in AMP

					    if (!empty($tableForAmpAds)) {
						    add_filter('the_content', ['RFWP_Amp', 'adToContent'], 500);
					    }

//				        add_action('the_content', ['RFWP_Amp', 'adToContent'], 50);
//		add_filter('amp_post_template_data', ['RFWP_Amp', 'checkContent'], 100);
//		add_action('amp_post_template_data', ['RFWP_Amp', 'checkContent'], 100);
//        add_filter('amp_content_sanitizers', ['RFWP_Amp', 'checkContent'], 100);
//		add_action('amp_content_sanitizers', ['RFWP_Amp', 'checkContent'], 100);
//        add_filter('amp_post_template_body_open', ['RFWP_Amp', 'checkContent'], 100);
//		add_action('amp_post_template_body_open', ['RFWP_Amp', 'checkContent'], 100);
//        add_filter('amp_content_embed_handlers', ['RFWP_Amp', 'checkContent'], 100);
//		add_action('amp_content_embed_handlers', ['RFWP_Amp', 'checkContent'], 100);
//        add_filter('pre_amp_render_post', ['RFWP_Amp', 'checkContent'], 100);
//		add_action('pre_amp_render_post', ['RFWP_Amp', 'checkContent'], 100);
			        }
                    RFWP_Logs::saveLogs(RFWP_Logs::AMP_LOG, 'is amp');
                } else {
                    RFWP_Logs::saveLogs(RFWP_Logs::AMP_LOG, 'not amp');
                }
			}

			return $var1;
		}

		public static function checkContent ($content, $var1 = 'nUn', $var2 = 'nUn') {
		    return $content;
        }

        public static function adToContentTest ($content) {
		    $ad = self::testAd1();
		    $content = $ad.$content;

		    return $content;
        }

        public static function adToContent ($content) {
	        try {
		        if (empty($content)) {
			        return $content;
		        }
		        global $wpdb;
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		        $ampAds = $wpdb->get_results("SELECT * FROM `{$GLOBALS['wpPrefix']}realbig_amp_ads` WRAA");
		        if (empty($ampAds)) {
			        return $content;
		        }

		        foreach ($ampAds AS $k => $item) {
			        $editedContent = $content;
			        $currentAdField = htmlspecialchars_decode($item->adField);
			        switch ($item->settingType) {
				        case 'begin':
					        $editedContent = $currentAdField.$editedContent;
					        break;
				        case 'end':
					        $editedContent = $editedContent.$currentAdField;
					        break;
				        case 'middle':
					        $contentLength = strlen($editedContent);
					        $contentHalfLength = floor($contentLength/2);
					        if ($contentHalfLength > 1) {
						        $firstHalf = mb_substr($editedContent, 0, ($contentHalfLength-1));
						        $secondHalf = mb_substr($editedContent, $contentHalfLength);
						        $secondHalf = preg_replace('~(\<\/[^>]+\>)~', '$1'.$currentAdField, $secondHalf, 1, $replCou);
						        if ($replCou > 0) {
							        $editedContent = $firstHalf.$secondHalf;
						        }
						        unset($replCou);
					        }
					        break;
				        case 'single':
					        if ($item->element!='img') {
						        if ($item->elementPosition < 1) {
							        $editedContent = preg_replace('~(\<'.$item->element.'[^>]*\>)~', '<rb_amp_ad_placeholder>$1', $editedContent, -1, $replCou);
						        } else {
							        $editedContent = preg_replace('~(\<\/'.$item->element.'\>)~', '$1<rb_amp_ad_placeholder>', $editedContent, -1, $replCou);
						        }
					        } else {
						        if ($item['elementPosition']<1) {
							        $editedContent = preg_replace('~(\<'.$item->element.'[^>]*\>)~', '<rb_amp_ad_placeholder>$1', $editedContent, -1, $replCou);
						        } else {
							        $editedContent = preg_replace('~(\<'.$item->element.'[^>]*\>)~', '$1<rb_amp_ad_placeholder>', $editedContent, -1, $replCou);
						        }
					        }
					        if ($replCou>0) {
						        if ($item->elementPlace > 0) {
							        $elementPlace = $item->elementPlace;
						        } else {
							        $elementPlace = (int)$replCou+(int)$item->elementPlace+1;
						        }
						        if ($elementPlace>0 && $elementPlace<=$replCou) {
							        $editedContent = preg_replace('~\<rb\_amp\_ad\_placeholder\>~', '', $editedContent, ($elementPlace-1));
							        $editedContent = preg_replace('~\<rb\_amp\_ad\_placeholder\>~', $currentAdField, $editedContent, 1);
						        }
						        $editedContent = preg_replace('~\<rb\_amp\_ad\_placeholder\>~', '', $editedContent);
					        }
					        unset($replCou);
					        break;
			        }
			        if (!empty($editedContent)) {
				        $content = $editedContent;
			        } else {
				        $editedContent = $content;
			        }
		        }
		        unset($k,$item);
	        }
	        catch (Exception $ex) {
		        $errorText = __FUNCTION__." error: ".$ex->getMessage();
		        RBAG_Logs::saveLogs('errorsLog', $errorText);
	        }
	        catch (Error $ex) {
		        $errorText = __FUNCTION__." error: ".$ex->getMessage();
		        RBAG_Logs::saveLogs('errorsLog', $errorText);
	        }

		    return $content;
        }

        public static function getAmpOption () {
	        $rb_ampSettings = null;

	        try {
		        if (!isset($GLOBALS['rb_ampSettings'])) {
			        $rb_ampSettings = get_option('rb_ampSettings');
			        if (!empty($rb_ampSettings)) {
				        $rb_ampSettings = json_decode($rb_ampSettings, true);
			        }
			        $GLOBALS['rb_ampSettings'] = $rb_ampSettings;
		        } else {
			        $rb_ampSettings = $GLOBALS['rb_ampSettings'];
		        }
	        }
	        catch (Exception $ex) {
		        $errorText = __FUNCTION__." error: ".$ex->getMessage();
		        RBAG_Logs::saveLogs('errorsLog', $errorText);
	        }
	        catch (Error $ex) {
		        $errorText = __FUNCTION__." error: ".$ex->getMessage();
		        RBAG_Logs::saveLogs('errorsLog', $errorText);
	        }

			return $rb_ampSettings;
        }

        public static function testAd1 () {
		    $ad =
                '<!doctype html>'
                .'<html amp4ads>'
                .'<head>'
                .'<meta charset="utf-8">'
                .'<title>My amphtml ad</title>'
                .'<meta name="viewport" content="width=device-width">'
                .'<script async src="https://cdn.ampproject.org/amp4ads-v0.js"></script>'
                .'<style amp4ads-boilerplate>body{visibility:hidden}</style>'
                .'</head>'
                .'<body>'
                .'<a target="_blank" href="https://www.amp.dev">'
                .'<amp-img width="300" height="250"'
                .'alt="Learn amp"'
                .'src="https://amp.dev/static/inline-examples/images/sea.jpg"></amp-img>'
                .'</a>'
                .'</body>'
                .'</html>';

		    return $ad;
        }
	}
}
