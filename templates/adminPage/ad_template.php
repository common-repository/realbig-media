<?php
$args = !empty($GLOBALS['rb_adminPage_adTemplate']) ? $GLOBALS['rb_adminPage_adTemplate'] : [];

$tagCategories = RFWP_getTagsCategories();
$tagString = $categoryString = "";

if (!empty($args['onTags'])) {
    $tags = explode(',', $args['onTags']);
    foreach ($tags as $tag) {
        $tagString .= (!empty($tagString) ? ',' : '') . " " .
            (isset($tagCategories['tags'][$tag]) ? '"' . $tagCategories['tags'][$tag] . '"' : $tag);
    }
    $tagString = "Выводить в" . $tagString;

} elseif (!empty($args['offTags'])) {
    $tags = explode(',', $args['offTags']);
    foreach ($tags as $tag) {
        $tagString .= (!empty($tagString) ? ',' : '') . " " .
            (isset($tagCategories['tags'][$tag]) ? '"' . $tagCategories['tags'][$tag] . '"' : $tag);
    }
    $tagString = "Не выводить в" . $tagString;
} else {
    $tagString = RFWP_Utils::getYesOrNo(0);

}

if (!empty($args['onCategories'])) {
    $tags = explode(',', $args['onCategories']);
    foreach ($tags as $tag) {
        $categoryString .= (!empty($categoryString) ? ',' : '') . " " .
            (isset($tagCategories['categories'][$tag]) ? '"' . $tagCategories['categories'][$tag] . '"' : $tag);
    }
    $categoryString = "Выводить в" . $categoryString;

} elseif (!empty($args['offCategories'])) {
    $tags = explode(',', $args['offCategories']);
    foreach ($tags as $tag) {
        $categoryString .= (!empty($categoryString) ? ',' : '') . " " .
            (isset($tagCategories['categories'][$tag]) ? '"' . $tagCategories['categories'][$tag] . '"' : $tag);
    }
    $categoryString = "Не выводить в" . $categoryString;
} else {
    $categoryString = RFWP_Utils::getYesOrNo(0);

}
?>

<div class="squads-blocks width-whole">
    <div class="element-separator">ID: <b><?php echo esc_html($args['block_number']); ?></b></div>
    <div class="element-separator">Тип отображения:
        <b><?php echo esc_html(RFWP_AdUtils::getSettingsType($args['setting_type']));
        if (in_array($args['setting_type'], [6, 7])) echo ": " . esc_html($args['elementPlace']) . " от начала текста" ?></b>
    </div>
    <div class="element-separator">Минимум символов: <b><?php echo esc_html($args['minSymbols']); ?></b></div>
    <div class="element-separator">Максимум символов: <b><?php echo esc_html($args['maxSymbols']); ?></b></div>
    <div class="element-separator">Минимум заголовков: <b><?php echo esc_html($args['minHeaders']); ?></b></div>
    <div class="element-separator">Максимум заголовков: <b><?php echo esc_html($args['maxHeaders']); ?></b></div>
    <div class="element-separator">Теги: <b><?php echo esc_html($tagString); ?></b></div>
    <div class="element-separator">Категории: <b><?php echo esc_html($categoryString); ?></b></div>
    <div class="element-separator">Расположение: <b><?php echo esc_html(ucfirst($args['elementCss'])); ?></b></div>
</div>