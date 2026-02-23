<?php
if (!defined('ABSPATH')) {
    exit;
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
        if (isset($args['picture']) && $args['picture'] && $args['picture'] !== '') {
            img_print_picture_tag(img: $args["picture"], max_size: "medium", classes: "default-card__pic");
        } else {
            include get_template_directory() . '/assets/icons/icon-file-image.svg';
        }
        if ($args['link_url'] && $args['link_url'] !== '') {
            echo "<span>" . $args['title'] . "</span></a>";
        } else {
            echo "</div>";
        }
        ?>

        <div class="default-card__inner tx-center">

            <p class="default-card__title"><?= $args["title"] ?></p>

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