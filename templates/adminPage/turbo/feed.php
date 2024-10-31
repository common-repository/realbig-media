<?php
$args = !empty($GLOBALS['rb_adminPage_args']) && !empty($GLOBALS['rb_adminPage_args']['turboOptions']) ? $GLOBALS['rb_adminPage_args']['turboOptions'] : [];
$index = 1;

?>

<h2>Описание ленты</h2>
<div class="element-separator">Имя RSS-ленты: <b><?php echo esc_html($args['name']); ?></b></div>
<div class="element-separator">Заголовок: <b><?php echo esc_html($args['title']); ?></b></div>
<div class="element-separator">Ссылка: <b><?php echo esc_html($args['url']); ?></b></div>
<div class="element-separator">Язык: <b><?php echo esc_html($args['lang']); ?></b></div>
<div class="element-separator">Описание: <b><?php echo esc_html($args['description']); ?></b></div>

<h2>Настройки ленты</h2>
<div class="element-separator">Количество записей: <b><?php echo esc_html($args['pagesCount']); ?></b></div>
<div class="element-separator">Разбитие RSS-ленты: <b><?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['divide']) ? 1 : 0)); ?></b></div>
<?php if (!empty($args['divide'])): ?>
<div class="element-separator">Делить RSS-ленту по: <b><?php echo esc_html($args['rssPartsSeparated']); ?></b></div>
<?php endif; ?>
<?php if (!empty($GLOBALS['rb_rssFeedUrls'])): ?>
    <div class="element-separator squads-blocks no-margin"><b>URL основной ленты</b>
        <div>
            <?php foreach ($GLOBALS['rb_rssFeedUrls'] AS $k => $item): ?>
                <?php if(get_option('permalink_structure')): ?>
                    <a target="_blank" href="<?php echo esc_url(home_url()) ?>/feed/<?php echo esc_attr($item); ?>"><?php echo esc_url(home_url()) ?>/feed/<?php echo esc_attr($item); ?></a><br>
                <?php else: ?>
                    <a target="_blank" href="<?php echo esc_url(home_url()) ?>/?feed=<?php echo esc_attr($item); ?>"><?php echo esc_url(home_url()) ?>/?feed=<?php echo esc_attr($item); ?></a><br>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php unset($k,$item); ?>
        </div>
    </div>
<?php endif; ?>


<h2>Выборочное отключение: <?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['selectiveOff']) ? 1 : 0)); ?></h2>
<?php if (!empty($args['trashRss'])): ?>
    <div class="element-separator">URL "мусорной" ленты:
        <?php if(get_option('permalink_structure')): ?>
            <a target="_blank" href="<?php echo esc_url($args['trashRss']); ?>"><?php echo esc_url($args['trashRss']); ?></a>
        <?php else: ?>
            <a target="_blank" href="<?php echo esc_url($args['trashRss']); ?>"><?php echo esc_url($args['trashRss']); ?></a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($args['trashRss'])): ?>
    <div class="element-separator">Отслеживание: <b><?php echo esc_html(RFWP_Utils::getYesOrNo(!empty($args['selectiveOffTracking']) ? 1 : 0)); ?></b></div>
    <div class="element-separator">Список удаляемых ссылок: <b><?php echo esc_html($args['selectiveOffField']); ?></b></div>
<?php endif; ?>

<h2>Полное отключение:  <?php echo esc_html(RFWP_Utils::getYesOrNo($args['onTurbo'] != 'true' ? 1 : 0)); ?></h2>
<?php if ($args['onTurbo'] != 'true'): ?>
<div class="element-separator">Протокол: <b><?php echo $args['onOffProtocol'] != 'default' ? esc_html($args['onOffProtocol']) : 'Не менять'; ?></b></div>
<?php endif; ?>