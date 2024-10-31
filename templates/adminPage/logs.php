<?php
$args = !empty($GLOBALS['rb_adminPage_args']) ? $GLOBALS['rb_adminPage_args'] : [];
$folder = plugin_dir_path(__FILE__) . '../../logs/';
$files = list_files(rtrim($folder, '/'));
global $wp_filesystem;
require_once(ABSPATH . 'wp-admin/includes/file.php');
WP_Filesystem();
?>

<?php if (!empty($files)): ?>
    <?php foreach (RFWP_Logs::LOGS as $type => $log): ?>
        <?php if (in_array($folder . $log, $files)): ?>
            <div class="element-separator most accordion-section">
                <div class="accordion-section-title"><?php echo esc_html($type); ?></div>
                <pre class="pre-wrap accordion-section-content"><?php echo esc_html($wp_filesystem->get_contents($folder . $log)) ?></pre>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <form method="post" class="ml-auto" name="logsForm" id="logsFormId">
        <input type="hidden" name="_csrf" value="<?php echo esc_attr($args['_csrf']) ?>" />
        <?php submit_button( 'Очистить все логи', 'primary', 'clearLogs') ?>
    </form>
<?php else: ?>
    Нет логов на данном сайте
<?php endif; ?>

<form class="element-separator most" method="post" name="enableLogsForm" id="enableLogsFormId">
    <div>
        <input type="hidden" name="tokenInput" id="tokenInputId" value="<?php echo esc_attr($GLOBALS['token']) ?>">
        <label><input type="checkbox" name="enable_logs" id="enable_logs_id" <?php echo esc_attr($args['enable_logs']) ?>>
            Включить сбор логов</label>
    </div>
    <input type="hidden" name="_csrf" value="<?php echo esc_attr($args['_csrf']) ?>" />
    <?php submit_button( 'Синхронизировать', 'primary', 'enableLogsButton' ) ?>
</form>

<hr class="element-separator most">

<div>
    <?php foreach (['Errors' => RFWP_Logs::ERRORS_LOG] as $type => $log): ?>
        <?php if (in_array($folder . $log, $files)): ?>
            <div class="element-separator most accordion-section">
                <div class="accordion-section-title"><?php echo esc_html($type); ?></div>
                <pre class="pre-wrap accordion-section-content"><?php echo esc_html($wp_filesystem->get_contents($folder . $log)) ?></pre>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<script>
    function accordion() {
        var blocks = document.getElementsByClassName('accordion-section-title');

        for (var i = 0; i < blocks.length; i++) {
            if (!blocks.hasOwnProperty(i)) continue;

            var block = blocks[i];

            block.addEventListener('click', function(el) {
                this.parentNode.classList.toggle('open');
            })
        }
    }

    accordion();
</script>