<?php
$ads = RFWP_getTurboAds();
?>

<?php if (!empty($ads)): ?>
<?php foreach ($ads as $ad): ?>
    <div class="squads-blocks width-whole">
        <div class="element-separator">ID: <b><?php echo esc_html($ad['id']); ?></b></div>
        <div class="element-separator">Рекламная сеть:
            <b><?php echo esc_html(RFWP_AdUtils::getTurboAdNetwork($ad['adNetwork'])); ?></b></div>
        <?php if ($ad['adNetwork'] == 'rsya'): ?>
            <div class="element-separator">РСЯ идентификатор: <b><?php echo esc_html($ad['adNetworkYandex']); ?></b></div>
        <?php elseif ($ad['adNetwork'] == 'adfox'): ?>
            <div class="element-separator">Код ADFOX: <b><?php echo esc_html($ad['adNetworkAdfox']); ?></b></div>
        <?php endif; ?>
        <div class="element-separator">Тип отображения:
            <b><?php echo esc_html(RFWP_AdUtils::getTurboSettingsType($ad['settingType'])); ?></b></div>
        <?php if ($ad['settingType'] == 'single'): ?>
            <div class="element-separator">Тег: <b><?php echo esc_html($ad['element']); ?></b></div>
            <div class="element-separator">Позиция тега: <b><?php echo $ad['elementPosition'] < 1 ? "До" : "После"; ?></b></div>
            <div class="element-separator">Место тега: <b><?php echo esc_html($ad['elementPlace']); ?></b></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php else: ?>
    <div>Нет настроенной рекламы</div>
<?php endif; ?>