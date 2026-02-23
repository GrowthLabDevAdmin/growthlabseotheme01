<?php
if (!defined('ABSPATH')) {
    exit;
}
foreach ($args as $key => $value) $$key = $value;
?>

<div class="ampersand-separator <?= esc_attr($classes); ?>">
    <hr>
    <?php include get_stylesheet_directory() . '/assets/img/separator-symbol.svg'; ?>
    <hr>
</div>