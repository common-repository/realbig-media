<?php
$args = !empty($GLOBALS['rb_adminPage_args']) && !empty($GLOBALS['rb_adminPage_args']['turboOptions']) ? $GLOBALS['rb_adminPage_args']['turboOptions'] : [];
$authors = ['enable' => 'Автор записи', 'disable' => 'Отключить указание автора'];
?>

<h2>Включить режим поддержки CSS: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['PostHtml']) ? 1 : 0)); ?></h2>


<h2>Указать дату публикации записей: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['PostDate']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['PostDate'])):
    $dates = ['create' => 'Дата создания', 'edit' => 'Дата изменения'];?>
    <div class="element-separator">Тип даты: <b><?php echo esc_html(!empty($dates[$args['PostDateType']]) ? $dates[$args['PostDateType']] : $args['PostDateType']); ?></b></div>
<?php endif; ?>

<h2>Добавить в начало записей "отрывок": <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['PostExcerpt']) ? 1 : 0)); ?></h2>


<h2>Добавить миниатюру к заголовку записи: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['Thumbnails']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['Thumbnails'])):
    $sizes = RFWP_getSavedThemeThumbnailSizes();?>
    <div class="element-separator">Тип даты: <b><?php echo !empty($sizes[$args['ThumbnailsSize']]) ? esc_html($sizes[$args['ThumbnailsSize']]) : ''; ?></b></div>
<?php endif; ?>

<h2>Автор записей</h2>
<div class="element-separator">Автор записей: <b><?php echo esc_html($args['PostAuthor'] == 'custom' ? $args['PostAuthorDirect'] :
            (!empty($authors[$args['PostAuthor']]) ? $authors[$args['PostAuthor']] : $args['PostAuthor'])); ?></b></div>

<h2>Описания изображений: <?php echo esc_html(RFWP_Utils::getEnableOrDisable($args['ImageDesc'])); ?></h2>


<h2>Добавить блок содержания на турбо-страницы: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['toc']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['toc'])):
    $types = ['post' => 'Posts', 'page' => 'Pages'];
    $position = ['beforeFirstH' => 'Перед первым заголовком', 'afterFirstH' => 'После первого заголовка',
        'postBegin' => 'В начале записи', 'postEnd' => 'В конце записи'];
    foreach ($args['tocPostTypes'] as &$type) $type = !empty($types[$type]) ? $types[$type] : $type; ?>
    <div class="element-separator">Типы записей для добавления блока содержания:
        <b><?php echo esc_html(!empty($args['tocPostTypes']) ? implode(', ', $args['tocPostTypes']) : RFWP_Utils::getYesOrNo(0)); ?></b></div>
    <div class="element-separator">Текст заголовка: <b><?php echo esc_html($args['tocTitleText']); ?></b></div>
    <div class="element-separator">Расположение блока:
        <b><?php echo esc_html(!empty($position[$args['tocPosition']]) ? $position[$args['tocPosition']] : $args['tocPosition']); ?></b></div>
    <div class="element-separator">Минимум заголовков: <b><?php echo esc_html($args['tocTitlesMin']); ?></b></div>
    <div class="element-separator">Уровни заголовков: <b><?php echo esc_html(str_replace(';', ', ', $args['tocTitlesLevels'])); ?></b></div>
<?php endif; ?>
