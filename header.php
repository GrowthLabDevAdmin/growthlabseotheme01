<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <?php wp_body_open(); ?>

    <?php
    global $post;
    $post_id = $post ? $post->ID : 0;

    $es = filterContentByLanguage() ? '_es' : '';
    $options = get_field_options('options' . $es);
    foreach ($options as $key => $value) $$key = $value;
    $phone_number = $contact_phone ?: $main_phone_number;
    ?>

    <header class="site-header <?= !is_404() && get_field('hero_style') !== "nohero" && $sticky_header ? "site-header--sticky" : "" ?>">

        <div class="site-header__wrapper container">

            <div class="site-header__logo">
                <a href="<?php echo esc_url(home_url('/' . $es)); ?>" class="site-logo">
                    <?php
                    if (function_exists('the_custom_logo') && has_custom_logo()) {
                        $custom_logo_id = get_theme_mod('custom_logo');
                        $image = wp_get_attachment_image_src($custom_logo_id, 'full');
                        img_print_picture_tag(img: $image[0], alt_text: get_bloginfo('name'), is_priority: true);
                    }
                    ?>
                    <span>Site Logo</span>
                </a>
            </div>

            <div class="site-header__navigation">
                <button class="mobile-menu-button" role="button" aria-label="Mobile Menu Button">
                    <svg viewBox="0 0 24 24" width="24" height="24">
                        <line x1="2" y1="4" x2="22" y2="4" />
                        <line x1="2" y1="12" x2="22" y2="12" />
                        <line x1="2" y1="20" x2="22" y2="20" />
                    </svg>
                    <span>Mobile Menu Button</span>
                </button>

                <?php
                if (has_nav_menu('main')) {
                    wp_nav_menu(
                        array(
                            'theme_location'  => 'main' . $es,
                            'container'          => 'nav',
                            'container_class' => 'main-nav',
                            'menu_class'      => 'main-nav__menu',
                            'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
                            'link_before'          => '<span>',
                            'link_after'              => '</span>'
                        )
                    );
                }
                ?>
            </div>

            <?php if ($cta_button): ?>
                <div class="site-header__cta">
                    <a href="<?= $cta_button['url'] ?>" class="cta-button btn btn--tertiary btn--arrow" target="<?= $cta_button['target'] ?>">

                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="cta-button__icon">
                            <g clip-path="url(#clip0_4184_2633)">
                                <path d="M5.48171 1.99214C5.39316 1.87823 5.28138 1.78447 5.15381 1.71707C5.02624 1.64968 4.88579 1.61021 4.74178 1.60127C4.59778 1.59233 4.45353 1.61414 4.3186 1.66523C4.18367 1.71633 4.06116 1.79555 3.95921 1.89764L2.40821 3.45014C1.68371 4.17614 1.41671 5.20364 1.73321 6.10514C3.04682 9.83652 5.18365 13.2243 7.98521 16.0171C10.7781 18.8187 14.1658 20.9555 17.8972 22.2691C18.7987 22.5856 19.8262 22.3186 20.5522 21.5941L22.1032 20.0431C22.2053 19.9412 22.2845 19.8187 22.3356 19.6837C22.3867 19.5488 22.4085 19.4046 22.3996 19.2606C22.3906 19.1166 22.3512 18.9761 22.2838 18.8485C22.2164 18.721 22.1226 18.6092 22.0087 18.5206L18.5482 15.8296C18.4265 15.7353 18.285 15.6697 18.1343 15.638C17.9836 15.6063 17.8276 15.6093 17.6782 15.6466L14.3932 16.4671C13.9547 16.5767 13.4953 16.5709 13.0598 16.4503C12.6242 16.3296 12.2273 16.0982 11.9077 15.7786L8.22371 12.0931C7.90386 11.7737 7.6722 11.3769 7.55128 10.9413C7.43036 10.5057 7.4243 10.0462 7.53371 9.60764L8.35571 6.32264C8.39307 6.17323 8.39601 6.01729 8.36431 5.86658C8.33261 5.71587 8.26709 5.57433 8.17271 5.45264L5.48171 1.99214ZM2.82671 0.766637C3.0892 0.504062 3.40455 0.300333 3.75182 0.168979C4.09908 0.0376255 4.47032 -0.0183475 4.84088 0.00477688C5.21144 0.0279013 5.57284 0.129594 5.90108 0.303102C6.22932 0.476611 6.5169 0.717965 6.74471 1.01114L9.43571 4.47014C9.92921 5.10464 10.1032 5.93114 9.90821 6.71114L9.08771 9.99614C9.04529 10.1663 9.04758 10.3445 9.09436 10.5135C9.14114 10.6825 9.23083 10.8365 9.35471 10.9606L13.0402 14.6461C13.1645 14.7703 13.3187 14.8601 13.488 14.9069C13.6573 14.9537 13.8358 14.9558 14.0062 14.9131L17.2897 14.0926C17.6746 13.9964 18.0764 13.9889 18.4646 14.0708C18.8529 14.1526 19.2174 14.3217 19.5307 14.5651L22.9897 17.2561C24.2332 18.2236 24.3472 20.0611 23.2342 21.1726L21.6832 22.7236C20.5732 23.8336 18.9142 24.3211 17.3677 23.7766C13.4095 22.3839 9.81561 20.1179 6.85271 17.1466C3.88164 14.1842 1.61562 10.5908 0.222706 6.63314C-0.320294 5.08814 0.167206 3.42764 1.27721 2.31764L2.82821 0.766637H2.82671Z" fill="#F4F3EE" />
                            </g>
                            <defs>
                                <clipPath id="clip0_4184_2633">
                                    <rect width="24" height="24" fill="white" />
                                </clipPath>
                            </defs>
                        </svg>

                        <span class="cta-button__text">
                            <?= $cta_button['title'] ?>
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.25 10C1.25 9.83423 1.31585 9.67526 1.43306 9.55805C1.55027 9.44084 1.70924 9.375 1.875 9.375H16.6163L12.6825 5.4425C12.5651 5.32514 12.4992 5.16597 12.4992 5C12.4992 4.83403 12.5651 4.67485 12.6825 4.5575C12.7999 4.44014 12.959 4.37421 13.125 4.37421C13.291 4.37421 13.4501 4.44014 13.5675 4.5575L18.5675 9.5575C18.6257 9.61555 18.6719 9.68452 18.7034 9.76045C18.7349 9.83639 18.7511 9.91779 18.7511 10C18.7511 10.0822 18.7349 10.1636 18.7034 10.2395C18.6719 10.3155 18.6257 10.3844 18.5675 10.4425L13.5675 15.4425C13.4501 15.5599 13.291 15.6258 13.125 15.6258C12.959 15.6258 12.7999 15.5599 12.6825 15.4425C12.5651 15.3251 12.4992 15.166 12.4992 15C12.4992 14.834 12.5651 14.6749 12.6825 14.5575L16.6163 10.625H1.875C1.70924 10.625 1.55027 10.5591 1.43306 10.4419C1.31585 10.3247 1.25 10.1658 1.25 10Z" fill="#F4F3EE" />
                            </svg>
                        </span>

                    </a>
                </div>
            <?php endif; ?>

            <?php if ($phone_number): ?>
                <div class="site-header__callout">

                    <div class="callout">
                        <?php if ($top_callout_first_line): ?>
                            <span><?= $top_callout_first_line ?></span>
                        <?php endif; ?>
                        <a href="tel:+1<?= get_flat_number($phone_number) ?>" class="callout__phone"><?= $phone_number ?></a>
                        <?php if ($top_callout_second_line): ?>
                            <span><?= $top_callout_second_line ?></span>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endif; ?>

        </div>
    </header>

    <?php
    $args = array(
        "hero_image_desktop_default" => $hero_image_desktop,
        "hero_image_tablet_default" => $hero_image_tablet,
        "hero_image_mobile_default" => $hero_image_mobile,
        "hero_cta_button_default" => $hero_cta_button,
    );

    if (!is_404()) {
        switch (get_field('hero_style')) {
            case 'home':
                get_template_part('template-parts/hero', 'homepage', $args);
                break;
            case 'default':
                get_template_part('template-parts/hero', 'default', $args);
                break;
            case 'home_2':
                get_template_part('template-parts/hero', 'homepage-v2', $args);
                break;
            case 'home_3':
                get_template_part('template-parts/hero', 'homepage-v3', $args);
                break;
            case 'home_4':
                get_template_part('template-parts/hero', 'homepage-v4', $args);
                break;
            case 'home_5':
                get_template_part('template-parts/hero', 'homepage-v5', $args);
                break;
            case 'home_6':
                get_template_part('template-parts/hero', 'homepage-v6', $args);
                break;
            case 'nohero':
                break;
            default:
                get_template_part('template-parts/hero', 'default', $args);
                break;
        }
    }
    ?>