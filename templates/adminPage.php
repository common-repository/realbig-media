<?php
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

include_once(plugin_dir_path(__FILE__) . "../RFWP_AdUtils.php");

$args = !empty($GLOBALS['rb_adminPage_args']) ? $GLOBALS['rb_adminPage_args'] : [];
$tab = !empty($args['tab']) ? $args['tab'] : 'sync';

$wp_filesystem = new \WP_Filesystem_Direct(null);
?>

<style>
    <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
     echo $wp_filesystem->get_contents( plugin_dir_path(__FILE__) . '../assets/css/page.css'); ?>
</style>

<h1>Настройки плагина «<?php echo esc_html(RFWP_Utils::getName()); ?>» <span>v<?php echo esc_html(RFWP_Utils::getVersion()); ?></span></h1>
<div class="wrap">
    <?php if (!empty($args['rbSettings'])): ?>
        <ul class="rfwp_tabs" data-url="1">
            <li <?php echo $tab == 'sync' ? 'class="active" ' : '' ?>data-tab="sync">Синхронизация</li>
            <li <?php echo $tab == 'info' ? 'class="active" ' : '' ?>data-tab="info">Информация</li>
            <li <?php echo $tab == 'cache' ? 'class="active" ' : '' ?>data-tab="cache">Кеш</li>
            <li <?php echo $tab == 'logs' ? 'class="active" ' : '' ?>data-tab="logs">Логи</li>
            <?php if (!empty($args['turboOptions'])): ?>
            <li <?php echo $tab == 'turbo' ? 'class="active" ' : '' ?>data-tab="turbo">Turbo</li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
    <div class="rfwp_white-blk">
        <div class="rfwp-blocks<?php echo $tab != 'sync' ? ' hidden' : '' ?>" data-tab="sync">
            <?php load_template(__DIR__ . '/adminPage/sync.php'); ?>
        </div>
        <?php if (!empty($args['rbSettings'])): ?>
            <div class="rfwp-blocks<?php echo $tab != 'info' ? ' hidden' : '' ?>" data-tab="info">
                <?php load_template(__DIR__ . '/adminPage/info.php'); ?>
            </div>
            <div class="rfwp-blocks<?php echo $tab != 'cache' ? ' hidden' : '' ?>" data-tab="cache">
                <?php load_template(__DIR__ . '/adminPage/cache.php'); ?>
            </div>
            <div class="rfwp-blocks<?php echo $tab != 'logs' ? ' hidden' : '' ?>" data-tab="logs">
                <?php load_template(__DIR__ . '/adminPage/logs.php'); ?>
            </div>
            <?php if (!empty($args['turboOptions'])): ?>
                <div class="rfwp-blocks<?php echo $tab != 'turbo' ? ' hidden' : '' ?>" data-tab="turbo">
                    <?php load_template(__DIR__ . '/adminPage/turbo.php'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function clickTabs() {
        let tabs = document.querySelectorAll('.rfwp_tabs li[data-tab]');
        for (let index = 0; index < tabs.length; index++) {
            if (!tabs.hasOwnProperty(index)) {
                continue;
            }

            let tab = tabs[index];
            tab.addEventListener('click', function() {
                let i;
                let parentBlk = this.parentNode.nextElementSibling;
                let block = parentBlk.querySelector('.rfwp-blocks[data-tab="' + this.getAttribute('data-tab') + '"]');
                let tabs = this.parentNode.querySelectorAll('.rfwp_tabs li[data-tab]');
                if (!this.classList.contains('active')) {
                    for (i = 0; i < tabs.length; i++) {
                        if (!tabs.hasOwnProperty(i)) {
                            continue;
                        }

                        tabs[i].classList.remove('active');
                    }

                    this.classList.add('active');

                    if (this.parentNode.hasAttribute('data-url')) {
                        var url = location.pathname + location.search;
                        var searchArr = [...url.matchAll(/(\?|\&)tab=([^&]*)/g)];
                        if (searchArr.length > 0) {
                            for (let i = 0; i < searchArr.length; i++) {
                                if (i > 0) {
                                    url = url.replace(searchArr[i][0], '');
                                } else {
                                    url = url.replace(searchArr[i][0], searchArr[i][1] + 'tab=' + this.getAttribute('data-tab'));
                                }
                            }
                        } else {
                            url += url.indexOf('?') >= 0 ? '&' : '?';
                            url += 'tab=' + this.getAttribute('data-tab');
                        }

                        window.history.pushState(null, document.title, url);
                    }

                    let blocks = parentBlk.querySelectorAll('.rfwp-blocks[data-tab]');
                    for (i = 0; i < blocks.length; i++) {
                        if (!blocks.hasOwnProperty(i) || blocks[i].parentNode !== parentBlk) {
                            continue;
                        }

                        blocks[i].classList.add('hidden');
                    }

                    if (!!block) {
                        block.classList.remove('hidden')
                    }

                }
            });
        }
    }

    clickTabs();
</script>