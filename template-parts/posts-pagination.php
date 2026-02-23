<?php
if (!defined('ABSPATH')) {
    exit;
}
$args = isset($args) && is_array($args) ? $args : array();
$args = wp_parse_args($args, array(
    'classes' => '',
    'paged' => max(1, (int) get_query_var('paged', 1)),
    'query' => null,
    'prev_text' => null,
    'next_text' => null,
    'mid_size' => 2,
    'end_size' => 1,
));

global $wp_query;
$query = $args['query'] instanceof WP_Query ? $args['query'] : $wp_query;
$paged = (int) $args['paged'];

if (empty($query) || $query->max_num_pages <= 1) {
    return;
}

$prev_arrow = '
            <svg width="11" height="20" viewBox="0 0 11 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M10.2823 0.220341C10.3522 0.290009 10.4076 0.372773 10.4454 0.46389C10.4832 0.555008 10.5027 0.65269 10.5027 0.751341C10.5027 0.849992 10.4832 0.947674 10.4454 1.03879C10.4076 1.12991 10.3522 1.21267 10.2823 1.28234L1.81184 9.75134L10.2823 18.2203C10.4232 18.3612 10.5023 18.5522 10.5023 18.7513C10.5023 18.9505 10.4232 19.1415 10.2823 19.2823C10.1415 19.4232 9.95051 19.5023 9.75134 19.5023C9.55218 19.5023 9.36117 19.4232 9.22034 19.2823L0.22034 10.2823C0.150495 10.2127 0.0950809 10.1299 0.0572712 10.0388C0.0194616 9.94767 0 9.84999 0 9.75134C0 9.65269 0.0194616 9.55501 0.0572712 9.46389C0.0950809 9.37277 0.150495 9.29001 0.22034 9.22034L9.22034 0.220341C9.29001 0.150496 9.37277 0.0950816 9.46389 0.057272C9.55501 0.0194623 9.65269 0 9.75134 0C9.84999 0 9.94767 0.0194623 10.0388 0.057272C10.1299 0.0950816 10.2127 0.150496 10.2823 0.220341Z" fill="#BC9061" />
            </svg>
            <span class="arrow__placeholder">Prev</span>
        ';

$next_arrow = '
            <svg width="11" height="20" viewBox="0 0 11 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M0.220588 0.220341C0.150743 0.290009 0.0953293 0.372773 0.057519 0.46389C0.0197096 0.555008 0.000247002 0.65269 0.000247002 0.751341C0.000247002 0.849992 0.0197096 0.947674 0.057519 1.03879C0.0953293 1.12991 0.150743 1.21267 0.220588 1.28234L8.69109 9.75134L0.220588 18.2203C0.0797577 18.3612 0.000640869 18.5522 0.000640869 18.7513C0.000640869 18.9505 0.0797577 19.1415 0.220588 19.2823C0.361418 19.4232 0.552424 19.5023 0.751588 19.5023C0.950751 19.5023 1.14176 19.4232 1.28259 19.2823L10.2826 10.2823C10.3524 10.2127 10.4078 10.1299 10.4457 10.0388C10.4835 9.94767 10.5029 9.84999 10.5029 9.75134C10.5029 9.65269 10.4835 9.55501 10.4457 9.46389C10.4078 9.37277 10.3524 9.29001 10.2826 9.22034L1.28259 0.220341C1.21292 0.150496 1.13016 0.0950816 1.03904 0.057272C0.94792 0.0194623 0.850239 0 0.751588 0C0.652937 0 0.555256 0.0194623 0.464138 0.057272C0.37302 0.0950816 0.290257 0.150496 0.220588 0.220341Z" fill="#BC9061" />
            </svg>
            <span class="arrow__placeholder">Next</span>
        ';

$pagination = paginate_links(array(
    'format' => '?paged=%#%',
    'current' => max(1, $paged),
    'total' => $query->max_num_pages,
    'prev_text' => $prev_arrow,
    'next_text' => $next_arrow,
    'type' => 'array',
    'add_args' => array(),
    'mid_size' => (int) $args['mid_size'],
    'end_size' => (int) $args['end_size'],
));

$container_classes = esc_attr($args['classes']);
?>
<div class="<?= $container_classes ?>">
    <?php
    /*
    * Generate pagination links as an array
    * Returns array of link HTML for custom markup
    */
    if (!empty($pagination)):
    ?>
        <ul class="pagination pagination-buttons">
            <?php
            foreach ($pagination as $page_link):
                // Add general pagination link class
                $page_link = str_replace('page-numbers', 'page-numbers pagination__link', $page_link);

                // Determine li class based on link type
                $li_class = 'pagination__item btn btn--secondary';

                if (strpos($page_link, 'prev') !== false) {
                    $li_class .= ' pagination__item--prev arrow arrow--prev';
                    $page_link = str_replace('prev', 'prev pagination__link--nav', $page_link);
                } elseif (strpos($page_link, 'next') !== false) {
                    $li_class .= ' pagination__item--next arrow arrow--next';
                    $page_link = str_replace('next', 'next pagination__link--nav', $page_link);
                } elseif (strpos($page_link, 'current') !== false) {
                    $li_class .= ' pagination__item--current is-active';
                    $page_link = str_replace('current', 'current pagination__link--active', $page_link);
                } elseif (strpos($page_link, 'dots') !== false) {
                    $li_class .= ' pagination__item--dots';
                    $page_link = str_replace('dots', 'dots pagination__link--dots', $page_link);
                } else {
                    $li_class .= ' pagination__item--number';
                }
            ?>
                <li class="<?= esc_attr($li_class) ?>">
                    <?= $page_link ?>
                </li>
            <?php
            endforeach;
            ?>
        </ul>
    <?php
    endif;
    ?>
</div>