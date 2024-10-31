<?php
/**
 * Created by PhpStorm.
 * User: furio
 * Date: 2019-07-15
 * Time: 15:32
 */

if (!defined("ABSPATH")) { exit;}

try {
	if (!function_exists('RFWP_add_toolbar_items')) {
		function RFWP_add_toolbar_items($admin_bar) {
//		$ppCurrentStatus = ['text' => 'idle', 'color' => 'green'];
            $arrayForArray = [
                'longCacheUse'=>RFWP_Cache::getLongCache(),
                'activeCache' =>RFWP_Cache::getActiveCache(),
                'syncAttempt' =>RFWP_Cache::getAttemptCache(),
                'syncProcess' =>RFWP_Cache::getProcessCache(),
                'cache'       =>RFWP_Cache::getCacheTimeout(),
                'mobileCache' =>RFWP_Cache::getMobileCache(),
                'tabletCache' =>RFWP_Cache::getTabletCache(),
                'desktopCache'=>RFWP_Cache::getDesktopCache(),
            ];
			$cachesArray = [];
			$cou = 0;
			foreach ($arrayForArray AS $k => $item) {
				$cachesArray[$cou] = [];
				$cachesArray[$cou]['name'] = $k;
				$cachesArray[$cou]['time'] = $item;
				$cou++;
            }
			unset($k,$item,$cou);

			$admin_bar->add_menu(array(
				'id'    => 'rb_item_1',
				'title' => '<span class="ab-icon dashicons dashicons-admin-site"></span> Realbig',
				'meta'  => array(
					'title' => __('My item'),
				),
			));
			$admin_bar->add_menu(array(
				'id'     => 'rb_sub_item_1',
				'parent' => 'rb_item_1',
				'title'  => 'Cache w expTime:',
				'meta'   => array(
					'title' => __('My Sub Menu Item'),
					'target' => '_blank',
					'class' => 'my_menu_item_class'
				),
			));
			foreach ($cachesArray AS $k => $item) {
				if (!empty($item['time']) && $item['time'] > 0) {
					$lctExpTime = $item['time'] - time();
					$admin_bar->add_menu(array(
						'id'     => 'rb_sub_item_1_'.($k+1),
						'parent' => 'rb_sub_item_1',
						'title'  => $item['name'].': '.'<span style="color: #92ffaf">'.$lctExpTime.'</span>',
					));
				}
			}

            $admin_bar->add_menu(array(
                'id'     => 'rb_sub_item_2',
                'parent' => 'rb_item_1',
                'title'  => 'Cache plugins status:',
                'meta'   => array(
                    'title' => __('My Sub Menu Item'),
                    'target' => '_blank',
                    'class' => 'my_menu_item_class'
                ),
            ));
			$cachePluginsStatus = RFWP_CachePlugins::checkCachePlugins();
			if (!empty($cachePluginsStatus)) {
			    $cpCou = 0;
                foreach ($cachePluginsStatus AS $k => $item) {
                    $cpCou++;
                    $admin_bar->add_menu(array(
                        'id'     => 'rb_sub_item_2_'.$cpCou,
                        'parent' => 'rb_sub_item_2',
                        'title'  => $k.': '.$item,
                    ));
                }
                unset($k, $item, $cpCou);
            }
		}
	}
	add_action('admin_bar_menu', 'RFWP_add_toolbar_items', 20000);
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

        RFWP_Utils::saveToRbSettings('admBar: ' . $ex->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($ex); ?></div><?php
}
catch (Error $er) {
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

        RFWP_Utils::saveToRbSettings('admBar: ' . $er->getMessage(), 'deactError');
	} catch (Exception $exIex) {
	} catch (Error $erIex) { }

	deactivate_plugins(plugin_basename( __FILE__ ));
	?><div style="margin-left: 200px; border: 3px solid red"><?php echo esc_html($er); ?></div><?php
}
