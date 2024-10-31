<?php
?>

<ul class="rfwp_tabs no-top-margin">
    <li class="active" data-tab="turbo-1">RSS-лента</li>
    <li data-tab="turbo-2">Оформление</li>
    <li data-tab="turbo-3">Блоки Яндекс.Турбо</li>
    <li data-tab="turbo-4">Счётчики</li>
    <li data-tab="turbo-5">Типы записей и исключения</li>
    <li data-tab="turbo-6">Фильтры</li>
    <li data-tab="turbo-7">Шаблоны</li>
    <li data-tab="turbo-8">Реклама</li>
</ul>
<div class="rfwp_white-blk">
    <div class="rfwp-blocks" data-tab="turbo-1">
        <?php load_template(__DIR__ . '/turbo/feed.php'); ?>
    </div>
    <div class="rfwp-blocks hidden" data-tab="turbo-2">
        <?php load_template(__DIR__ . '/turbo/design.php'); ?>
    </div>
    <div class="rfwp-blocks hidden" data-tab="turbo-3">
        <?php load_template(__DIR__ . '/turbo/blocks.php'); ?>
    </div>
    <div class="rfwp-blocks hidden" data-tab="turbo-4">
        <?php load_template(__DIR__ . '/turbo/counts.php'); ?>
    </div>
    <div class="rfwp-blocks hidden" data-tab="turbo-5">
        <?php load_template(__DIR__ . '/turbo/types.php'); ?>
    </div>
    <div class="rfwp-blocks hidden" data-tab="turbo-6">
        <?php load_template(__DIR__ . '/turbo/filters.php'); ?>
    </div>
    <div class="rfwp-blocks hidden" data-tab="turbo-7">
        <?php load_template(__DIR__ . '/turbo/templates.php'); ?>
    </div>
    <div class="rfwp-blocks hidden" data-tab="turbo-8">
        <?php load_template(__DIR__ . '/turbo/ads.php'); ?>
    </div>
</div>