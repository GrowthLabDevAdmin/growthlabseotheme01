<?php
if (!defined('ABSPATH')) {
    exit;
}

$es = filterContentByLanguage() ? '_es' : '';
$options = get_field_options('options' . $es);

$tag = $args["tag"];

if (!$args["tag"] || $args["tag"] === "") {
    $tag = $options['posts_default_title_tag'] ?: "p";
}

?>
<article class="post-card <?= $args["classes"] ?>">
    <div class="post-card__wrapper">

        <?php
        if ($args['link_url']) {
            echo '<a href="' . esc_url($args['link_url']) . '" class="post-card__pic-wrapper" target="' . esc_attr($args['link_target']) . '" aria-label="' . esc_attr($args['title']) . '">';
        } else {
            echo "<div class='post-card__pic-wrapper'>";
        }
        if (isset($args['picture']) && $args['picture']) {
            img_print_picture_tag(img: $args["picture"], max_size: "large", min_size: "medium_large", classes: "post-card__pic");
        } elseif (get_field_options("options")["posts_default_image"] && !empty(get_field_options("options")["posts_default_image"])) {
            img_print_picture_tag(img: get_field_options("options")["posts_default_image"], max_size: "large", min_size: "medium_large", classes: "post-card__pic");
        } else {
            include get_stylesheet_directory() . '/assets/icons/icon-file-image.svg';
        }
        if ($args['link_url']) {
            echo "</a>";
        } else {
            echo "</div>";
        }

        ?>

        <div class="post-card__inner">
            <span class="post-card__meta"><?= $args["meta"] ?></span>

            <<?= $tag ?> class="post-card__title"><?= $args["title"] ?></<?= $tag ?>>

            <p class="post-card__content"><?= $args["excerpt"] ?></p>

            <?php if ($args['link_url']): ?>
                <div class="post-card__btn">
                    <a href="<?= esc_url($args['link_url']) ?>" target="<?= esc_attr($args['link_target']) ?>" class="btn btn--secondary" aria-label="Read More">
                        <span>Read More</span>
                    </a>
                </div>
            <?php endif ?>
        </div>
    </div>
</article>