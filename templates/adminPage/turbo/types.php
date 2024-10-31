<?php
$args = !empty($GLOBALS['rb_adminPage_args']) && !empty($GLOBALS['rb_adminPage_args']['turboOptions']) ? $GLOBALS['rb_adminPage_args']['turboOptions'] : [];

$types = ['post' => 'Posts', 'page' => 'Pages'];
$typesArr = explode(';', $args['typesPost']);
foreach ($typesArr as &$type) {
    $type = !empty($types[$type]) ? $types[$type] : $type;
}

$typesIncludes = ['exclude' => 'Все таксономии, кроме исключенных', 'include' => 'Только указанные таксономии']; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
?>

<div class="element-separator most">Типы записей: <b><?php echo esc_html(implode(', ', $typesArr)); ?></b></div>
<div class="element-separator">Включить в RSS:
    <b><?php echo !empty($typesIncludes[$args['typesIncludes']]) ? esc_html($typesIncludes[$args['typesIncludes']]) : ''; ?></b></div>
<?php if (!empty($args['typesIncludes']) == 'exclude'): ?>
    <div class="element-separator">Таксономии для исключения: <b><?php echo esc_html($args['typesTaxExcludes']); ?></b></div>
<?php elseif (!empty($args['typesIncludes']) == 'include'): ?>
    <div class="element-separator">Таксономии для добавления: <b><?php echo esc_html($args['typesTaxIncludes']); ?></b></div>
<?php endif; ?>
<div class="element-separator most">Типы записей: <b><?php echo esc_html(implode(', ', $typesArr)); ?></b></div>
