<?php
/*Template Name: Template Full Width*/
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>

<?php while (have_posts()) {
    the_post();
    the_content();
} ?>

<?php get_footer() ?>