<?php
$args = !empty($GLOBALS['rb_adminPage_args']) && !empty($GLOBALS['rb_adminPage_args']['turboOptions']) ? $GLOBALS['rb_adminPage_args']['turboOptions'] : [];
$menus = RFWP_getMenuList();
?>

<h2>Меню: <?php echo $args['menu'] != 'not_use' ? (!empty($menus[$args['menu']]) ? esc_html($menus[$args['menu']]) : '') : 'Не использовать' ?></h2>

<h2>Добавить блок "Поделиться" на турбо-страницы: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockShare']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['blockShare'])): ?>
    <div class="element-separator">Порядок социальных сетей: <b><?php echo esc_html(str_replace(',', ', ', $args['blockShareOrder'])); ?></b></div>
<?php endif; ?>

<h2>Добавить блок обратной связи на турбо-страницы: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockFeedback']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['blockFeedback'])):?>
    <?php load_template(__DIR__ . '/blocks/feedback.php'); ?>
<?php endif; ?>

<h2>Добавить комментарии к турбо-страницам: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockComments']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['blockComments'])):
    $sort = ['new_in_begin' => 'В начале новые комментарии', 'old_in_begin' => 'В начале старые комментарии']; ?>
    <div class="element-separator">Добавить аватары к комментариям:
        <b><?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockCommentsAvatars']) ? 1 : 0)); ?></b></div>
    <div class="element-separator">Число комментариев: <b><?php echo esc_html($args['blockCommentsCount']); ?></b></div>
    <div class="element-separator">Сортировка:
        <b><?php echo esc_html(!empty($sort[$args['blockCommentsSort']]) ? $sort[$args['blockCommentsSort']] : $sort['old_in_begin']); ?></b></div>
    <div class="element-separator">Добавить дату к комментариям:
        <b><?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockCommentsDate']) ? 1 : 0)); ?></b></div>
    <div class="element-separator">Использовать древовидность:
        <b><?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockCommentsTree']) ? 1 : 0)); ?></b></div>
<?php endif; ?>

<h2>Добавить блок похожих записей на турбо-страницы: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockRelated']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['blockRelated'])):
    $sizes = RFWP_getSavedThemeThumbnailSizes(); ?>
    <div class="element-separator">Количество похожих записей: <b><?php echo esc_html($args['blockRelatedCount']); ?></b></div>
    <div class="element-separator">Ограничение по дате: <b><?php echo esc_html($args['blockRelatedDateLimitation']); ?></b></div>
    <div class="element-separator">Миниатюра для похожих записей:
        <b><?php echo !empty($sizes[$args['blockRelatedDateLimitation']]) ? esc_html($sizes[$args['blockRelatedDateLimitation']]) : ''; ?></b></div>
    <div class="element-separator">Непрерывная лента статей:
        <b><?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockRelatedUnstopable']) ? 1 : 0)); ?></b></div>
    <div class="element-separator">Кеширование:
        <b><?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockRelatedCaching']) ? 1 : 0)); ?></b></div>
    <?php if (!empty($args['blockRelatedCaching'])): ?>
        <div class="element-separator">Время жизни кэша: <b><?php echo esc_html($args['blockRelatedCachelifetime']); ?></b></div>
    <?php endif; ?>
<?php endif; ?>

<h2>Добавить рейтинг на турбо-страницы: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockRating']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['blockRating'])): ?>
    <div class="element-separator">Диапазон оценок:
        <b>От <?php echo esc_html($args['blockRatingFrom']); ?> до <?php echo esc_html($args['blockRatingTo']); ?></b></div>
<?php endif; ?>

<h2>Добавить поиск на турбо-страницы: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['blockSearch']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['blockSearch'])):
    $position = ['postBegin' => 'В начале записи', 'postEnd' => 'В конце записи']; ?>
    <div class="element-separator">Текст по умолчанию: <b><?php echo esc_html($args['blockSearchDefaultText']); ?></b></div>
    <div class="element-separator">Расположение блока:
        <b><?php echo !empty($position[$args['blockSearchPosition']]) ? esc_html($position[$args['blockSearchPosition']]) : ''; ?></b></div>
<?php endif; ?>