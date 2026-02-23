<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="testimonial-card <?= $args["classes"] ?>">
    <div class="testimonial-card__wrapper">
        <div class="testimonial-card__inner">

            <div class="testimonial-card__header">
                <?php if (isset($args['picture']) && $args['picture']) img_print_picture_tag(img: $args['picture'], classes: "testimonial-card__pic"); ?>
                <?php if ($args["source"]): ?>
                    <div class="testimonial-card__src">
                        <?php
                        switch ($args["source"]) {
                            case 'fb':
                                include get_template_directory() . '/assets/icons/icon-facebook.svg';
                                break;
                            case 'inst':
                                include get_template_directory() . '/assets/icons/icon-instagram.svg';
                                break;
                            case 'gg':
                                include get_template_directory() . '/assets/icons/icon-google.svg';
                                break;
                            case 'lin':
                                include get_template_directory() . '/assets/icons/icon-linkedin.svg';
                                break;
                            case 'x':
                                include get_template_directory() . '/assets/icons/icon-twitter-x.svg';
                                break;
                            default:
                                break;
                        }
                        ?>
                    </div>
                <?php endif ?>

                <div class="testimonial-card__quote">
                    <?php include get_template_directory() . '/assets/icons/icon-quote.svg'; ?>
                </div>
            </div>

            <blockquote class="testimonial-card__content">
                <p>
                    "<?= $args["content"] ?>"

                    <?php if ($args['link_url']): ?>
                        <a href="<?= $args['link_url'] ?>" target='_blank'>Read More</a>
                    <?php endif ?>
                </p>
            </blockquote>

            <div class=" testimonial-card__stars">
                <?php
                $i = 1;
                while ($i <= 5) {
                    include get_template_directory() . '/assets/icons/icon-star.svg';
                    $i++;
                }
                ?>
            </div>

            <p class="testimonial-card__author">
                <?= $args["author"] ?>
                <span><?= $args["role"]  ?></span>
            </p>

        </div>
    </div>
</div>