<?php
$args = !empty($GLOBALS['rb_adminPage_args']) ? $GLOBALS['rb_adminPage_args'] : [];
?>

<form method="post" name="tokenForm" id="tokenFormId">
    <label><span class="element-separator" style="font-size: 16px">Токен</span><br/>
        <span style="display: flex; align-items: flex-start">
                    <input class="element-separator" name="tokenInput" id="tokenInputId" value="<?php echo esc_attr($GLOBALS['token']) ?>"
                           style="min-width: 280px" required>
                </span>
    </label>
    <div class="element-separator">
        <label><input type="checkbox" name="statusRefresher" id="statusRefresher">
            обновить проверку</label>
    </div>
    <?php if (!empty($args['killRbAvailable'])): ?>
        <div class="element-separator">
            <label><input type="checkbox" name="kill_rb" id="kill_rb_id" <?php echo esc_attr($args['killRbCheck']) ?>>
                Kill connection to rotator</label>
        </div>
    <?php endif; ?>
    <div class="element-separator">
        <label><input type="checkbox" name="cache_clear" id="cache_clear_id" <?php echo esc_attr($args['cache_clear']) ?>>
            Очистить кэш</label>
    </div>
    <?php submit_button( 'Синхронизировать', 'primary', 'saveTokenButton' ) ?>
    <?php if (!empty($GLOBALS['tokenStatusMessage'])): ?>
        <span name="rezultDiv" style="font-size: 16px"><?php echo esc_html($GLOBALS['tokenStatusMessage']) ?></span>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['connection_request_rezult']) && $GLOBALS['connection_request_rezult'] != 'success'): ?>
        <div class="element-separator"><?php echo esc_html($GLOBALS['connection_request_rezult']) ?></div>
    <?php endif; ?>
    <?php if (!empty($args['devMode'])): ?>
        <?php submit_button( 'Check-Ip', 'big', 'checkIp') ?>
        <?php if (!empty($args['curlResult'])): ?>
            <span id="ip-result"><?php echo esc_html($args['curlResult']) ?></span>
        <?php endif; ?>
    <?php endif; ?>

    <input type="hidden" name="_csrf" value="<?php echo esc_attr($args['_csrf']) ?>" />
</form>

<?php if(!empty($GLOBALS['tokenTimeUpdate']) && $GLOBALS['tokenTimeUpdate'] != 'never'):
    $timeOffset = ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ); ?>
    <div style="font-size: 16px;margin-top: 30px;">
        <div class="element-separator more" style="color: <?php echo esc_html($GLOBALS['statusColor']) ?>">
            Время последней синхронизации: <?php echo esc_html(date_i18n('Y-m-d H:i:s', $GLOBALS['tokenTimeUpdate'] + $timeOffset)) ?></div>
        <?php if (!empty(RFWP_Cache::getAttemptCache()) || $GLOBALS['tokenTimeUpdate'] + RFWP_getPeriodSync() * 3 > time()): ?>
            <div class="element-separator more" style="font-weight: bold">Время следующей автосинхронизации:
                <?php if (!empty(RFWP_Cache::getAttemptCache())): ?>
                    <?php echo esc_html(date_i18n('Y-m-d H:i:s', RFWP_Cache::getAttemptCache() + $timeOffset)); ?>
                <?php elseif (wp_next_scheduled('rb_cron_hook')): ?>
                    <?php echo esc_html(date_i18n('Y-m-d H:i:s', wp_next_scheduled('rb_cron_hook') + $timeOffset)); ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="element-separator more" style="font-weight: bold; color: red;">Проблема с автосинхронизацией</div>
        <?php endif; ?>
    </div>
<?php endif; ?>