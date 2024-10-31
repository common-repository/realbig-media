<?php
$args = !empty($GLOBALS['rb_adminPage_args']) && !empty($GLOBALS['rb_adminPage_args']['turboOptions']) ? $GLOBALS['rb_adminPage_args']['turboOptions'] : [];
?>

<?php if (!empty($args['couYandexMetrics']) | !empty($args['couLiveInternet']) || !empty($args['couGoogleAnalytics'])): ?>
    <?php if (!empty($args['couYandexMetrics'])): ?>
        <div class="element-separator more">Яндекс.Метрика: <b><?php echo esc_html($args['couYandexMetrics']); ?></b></div>
    <?php endif; ?>
    <?php if (!empty($args['couLiveInternet'])): ?>
        <div class="element-separator more">LiveInternet: <b><?php echo esc_html($args['couLiveInternet']); ?></b></div>
    <?php endif; ?>
    <?php if (!empty($args['couGoogleAnalytics'])): ?>
        <div class="element-separator more">Google Analytics: <b><?php echo esc_html($args['couGoogleAnalytics']); ?></b></div>
    <?php endif; ?>
<?php else: ?>
    <div>Не указано счетчиков</div>
<?php endif; ?>