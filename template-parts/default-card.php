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

<div class="default-card <?= $args["classes"] ?>">
    <div class="default-card__wrapper">

        <?php
        if ($args['link_url'] && $args['link_url'] !== '') {
            echo "<a href=" . $args['link_url'] . " class='default-card__pic-wrapper' target=" . $args['link_target'] . ">";
        } else {
            echo "<div class='default-card__pic-wrapper'>";
        }
        if (isset($args['picture']) && $args['picture']) {
            img_print_picture_tag(img: $args["picture"], max_size: "medium", classes: "default-card__pic");
        } elseif (get_field_options("options")["posts_default_image"] && !empty(get_field_options("options")["posts_default_image"])) {
            img_print_picture_tag(img: get_field_options("options")["posts_default_image"], max_size: "medium", classes: "default-card__pic");
        } else {
            include get_stylesheet_directory() . '/assets/icons/icon-file-image.svg';
        }
        if ($args['link_url'] && $args['link_url'] !== '') {
            echo "<span>" . $args['title'] . "</span></a>";
        } else {
            echo "</div>";
        }
        ?>

        <div class="default-card__inner tx-center">

            <<?= $tag ?> class="default-card__title"><?= $args["title"] ?></<?= $tag ?>>

            <p class="default-card__content"><?= $args["content"] ?></p>

            <?php if ($args['link_url'] && $args['link_url'] !== ''): ?>
                <div class="default-card__btn">
                    <a href="<?= $args['link_url'] ?>" target="<?= $args['link_target'] ?>" class="btn btn--secondary">
                        <span>Read More</span>
                    </a>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>