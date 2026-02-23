<?php

/**
 * Responsive Image Helper Functions
 * Generates <picture> elements with WebP support and multiple breakpoints
 *
 * This version:
 * - Detects image sizes registered by WP (including custom add_image_size in functions.php)
 * - Maps sizes to breakpoints using heuristics
 * - Generates sources (including WebP if available) for each breakpoint
 * - Auto-detects cover sizes when is_cover=true
 */

/**
 * SUGGESTED BREAKPOINTS (adjustable)
 * - mobile : 0 - 599
 * - tablet : 600 - 1023
 * - ldpi   : 1024 - 1199
 * - mdpi   : 1200 - 1439
 * - hdpi   : 1440+
 */
$GLOBALS['breakpoints'] = [
    'mobile' => '0px',
    'tablet' => '600px',
    'ldpi'   => '1024px',
    'mdpi'   => '1200px',
    'hdpi'   => '1440px',
];

// Initialize size list: read WP registered sizes and ensure 'full' exists
function po_init_sizes()
{
    $sizes = [];

    // Get WP default sizes
    if (function_exists('get_intermediate_image_sizes')) {
        $sizes = (array) get_intermediate_image_sizes();
    }

    // Add custom sizes from $_wp_additional_image_sizes
    global $_wp_additional_image_sizes;

    if (is_array($_wp_additional_image_sizes)) {
        foreach ($_wp_additional_image_sizes as $size_name => $size_data) {
            if (!in_array($size_name, $sizes, true)) {
                $sizes[] = $size_name;
            }
        }
    }

    // ensure 'full' is present
    if (!in_array('full', $sizes, true)) {
        $sizes[] = 'full';
    }

    // normalize to indexed array
    $GLOBALS['sizes'] = array_values($sizes);
}
// Initialize sizes after WordPress loads (to ensure custom sizes are registered)
add_action('after_setup_theme', 'po_init_sizes', 999);

// Optional explicit mapping (complements heuristics)
$GLOBALS['preferred_size_map'] = [
    // Add explicit mappings here if needed:
    // 'cover-desktop' => 'mdpi',
    // 'cover-tablet'  => 'ldpi',
    // 'cover-mobile'  => 'mobile',
    // 'featured-small'=> 'mobile',
];

// Heuristic: map a WP image size name to a breakpoint
function po_map_size_to_breakpoint(string $size): string
{
    // Honor explicit overrides set by user first
    if (!empty($GLOBALS['preferred_size_map'][$size])) {
        return $GLOBALS['preferred_size_map'][$size];
    }

    $s = strtolower($size);

    // Keep a small set of guaranteed manual mappings for your custom sizes
    $manual_overrides = [
        'cover-desktop'   => 'mdpi',
        'cover-tablet'    => 'ldpi',
        'cover-mobile'    => 'mobile',
        'featured-small' => 'mobile',
    ];

    // If the size is one of your registered custom names and has a manual mapping, return it
    if (isset($manual_overrides[$s]) && in_array($s, $GLOBALS['sizes'], true)) {
        return $manual_overrides[$s];
    }

    // Build a map of registered sizes => widths (when available)
    global $_wp_additional_image_sizes;
    $sizes_with_width = [];

    foreach ($GLOBALS['sizes'] as $registered_size) {
        $k = strtolower($registered_size);
        if (!empty($_wp_additional_image_sizes[$registered_size]['width'])) {
            $sizes_with_width[$k] = (int) $_wp_additional_image_sizes[$registered_size]['width'];
            continue;
        }

        // WP default sizes stored in options (may be 0 => unconstrained)
        switch ($registered_size) {
            case 'thumbnail':
                $sizes_with_width[$k] = (int) get_option('thumbnail_size_w');
                break;
            case 'medium':
                $sizes_with_width[$k] = (int) get_option('medium_size_w');
                break;
            case 'medium_large':
                // medium_large is sometimes stored differently; try option then fallback 768
                $sizes_with_width[$k] = (int) get_option('medium_large_size_w') ?: (int) get_option('medium_size_w');
                break;
            case 'large':
                $sizes_with_width[$k] = (int) get_option('large_size_w');
                break;
            case 'full':
            default:
                // unknown/default: set 0 to indicate "no specific width"
                $sizes_with_width[$k] = 0;
                break;
        }
    }

    // If we have a numeric width for this size, map it to breakpoint by thresholds
    $width = $sizes_with_width[$s] ?? 0;
    if ($width > 0) {
        // Thresholds chosen to match suggested breakpoints:
        // mobile <=599, tablet <=1023, ldpi <=1199, mdpi <=1439, hdpi >=1440
        if ($width <= 599) {
            return 'mobile';
        }
        if ($width <= 1023) {
            return 'tablet';
        }
        if ($width <= 1199) {
            return 'ldpi';
        }
        if ($width <= 1439) {
            return 'mdpi';
        }
        return 'hdpi';
    }

    // Fall back to exact name matches for known WP defaults or clear names
    $defaults = [
        'thumbnail'    => 'mobile',
        'medium'       => 'tablet',
        'medium_large' => 'tablet',
        'large'        => 'ldpi',
        'full'         => 'hdpi',
    ];
    if (isset($defaults[$s])) {
        return $defaults[$s];
    }

    // Token-based boundary matching as a last resort (match whole token separated by '-' or '_')
    // This avoids accidental matches inside other words (e.g. "automobile")
    $token_map = [
        'mobile'  => 'mobile',
        'tablet'  => 'tablet',
        'ldpi'    => 'ldpi',
        'mdpi'    => 'mdpi',
        'hdpi'    => 'hdpi',
        'small'   => 'mobile',
        'large'   => 'ldpi',
        'desktop' => 'mdpi',
    ];

    foreach ($token_map as $token => $bp) {
        if (preg_match('/(^|[-_])' . preg_quote($token, '/') . '($|[-_])/', $s)) {
            return $bp;
        }
    }

    // Final fallback
    return 'hdpi';
}

// Return preferred WP size name for a given breakpoint (searching sizes by priority)
function po_get_preferred_size_for_breakpoint(string $breakpoint): ?string
{
    // prefer larger/custom sizes by reversing the registered list
    $sizes = array_reverse($GLOBALS['sizes']);
    foreach ($sizes as $size) {
        if (po_map_size_to_breakpoint($size) === $breakpoint) {
            return $size;
        }
    }
    return null;
}

/**
 * Detect all cover sizes available and sort them by width (descending)
 * Returns array of size names that contain 'cover' in their name
 */
function po_detect_cover_sizes(): array
{
    global $_wp_additional_image_sizes;

    $cover_sizes = [];

    foreach ($GLOBALS['sizes'] as $size) {
        $size_lower = strtolower($size);

        // Include if name contains 'cover' or if it's 'full'
        if (strpos($size_lower, 'cover') !== false || $size === 'full') {
            $width = 0;

            if ($size === 'full') {
                // full gets highest priority
                $width = PHP_INT_MAX;
            } elseif (!empty($_wp_additional_image_sizes[$size]['width'])) {
                $width = (int) $_wp_additional_image_sizes[$size]['width'];
            }

            $cover_sizes[] = [
                'name' => $size,
                'width' => $width,
                'breakpoint' => po_map_size_to_breakpoint($size)
            ];
        }
    }

    // Sort by width descending (largest first)
    usort($cover_sizes, function ($a, $b) {
        return $b['width'] - $a['width'];
    });

    return $cover_sizes;
}

// Helpers: create <source> and <img> tags
function img_create_source_tag(string $srcset, string $type, ?string $media = null): string
{
    $srcset_attr = "srcset='" . esc_url($srcset) . "'";
    $type_attr = "type='" . esc_attr($type) . "'";
    $media_attr = $media ? " media='" . esc_attr($media) . "'" : '';

    return "<source {$srcset_attr} {$type_attr}{$media_attr}>";
}

function img_create_img_tag(string $src, int $width = 0, int $height = 0, array $attrs = []): string
{
    $src_attr = "src='" . esc_url($src) . "'";
    $width_attr = $width ? "width='" . (int)$width . "'" : '';
    $height_attr = $height ? "height='" . (int)$height . "'" : '';
    $alt_attr = "alt='" . esc_attr($attrs['alt'] ?? '') . "'";
    $loading_attr = "loading='" . ($attrs['loading'] ?? 'lazy') . "'";
    $fetchpriority_attr = (!empty($attrs['fetchpriority']) && $attrs['fetchpriority'] !== 'auto') ? " fetchpriority='{$attrs['fetchpriority']}'" : '';
    $decoding_attr = "decoding='" . ($attrs['decoding'] ?? 'async') . "'";
    $extra = $attrs['extra'] ?? '';

    $parts = array_filter([$src_attr, $width_attr, $height_attr, $alt_attr, $loading_attr . $fetchpriority_attr, $decoding_attr]);
    return "<img " . implode(' ', $parts) . "{$extra}>";
}

function img_wrap_picture(array $sources, string $img_tag, array $attrs): string
{
    $id_attr = !empty($attrs['id']) ? "id='" . esc_attr($attrs['id']) . "'" : '';
    $class_attr = !empty($attrs['class']) ? "class='" . esc_attr($attrs['class']) . "'" : '';
    $picture_attrs = trim($id_attr . ' ' . $class_attr);

    $picture = $picture_attrs ? "<picture {$picture_attrs}>" : "<picture>";
    $picture .= implode('', $sources);
    $picture .= $img_tag;
    $picture .= "</picture>";

    return $picture;
}

// Keep parsing helpers (no major logic changes)
function img_get_empty_fields(): array
{
    return [
        'sizes' => [],
        'urls' => [],
        'alt' => '',
        'title' => '',
        'type' => 'image/jpeg',
    ];
}

function img_parse_acf_image(array $img): array
{
    $sizes_urls = [];
    $sizes_dimensions = [];

    foreach ($GLOBALS['sizes'] as $size) {
        if ($size === 'full') {
            $sizes_urls[$size] = $img['url'];
            $sizes_dimensions[$size] = [
                'width' => (int)$img['width'],
                'height' => (int)$img['height'],
            ];
        } else {
            $sizes_urls[$size] = $img['sizes'][$size] ?? $img['url'];
            $sizes_dimensions[$size] = [
                'width' => (int)($img['sizes']["{$size}-width"] ?? $img['width']),
                'height' => (int)($img['sizes']["{$size}-height"] ?? $img['height']),
            ];
        }
    }

    return [
        'sizes' => $sizes_dimensions,
        'urls' => $sizes_urls,
        'alt' => $img['alt'] ?? '',
        'title' => $img['title'] ?? '',
        'type' => $img['mime_type'] ?? 'image/jpeg',
    ];
}

function img_parse_url_image(string $img_url, bool $is_webp): array
{
    $img_id = attachment_url_to_postid($img_url);

    if (!$img_id) {
        return img_get_empty_fields();
    }

    $img_meta = wp_get_attachment_metadata($img_id);

    if (!$img_meta) {
        return img_get_empty_fields();
    }

    $img_type = $is_webp ? 'image/webp' : get_post_mime_type($img_id);
    $img_extension = $is_webp ? '.webp' : '';
    $sizes_urls = [];
    $sizes_dimensions = [];

    foreach ($GLOBALS['sizes'] as $size) {
        $sizes_urls[$size] = wp_get_attachment_image_url($img_id, $size) . $img_extension;

        if ($size === 'full') {
            $sizes_dimensions[$size] = [
                'width' => (int)$img_meta['width'],
                'height' => (int)$img_meta['height'],
            ];
        } else {
            $sizes_dimensions[$size] = [
                'width' => (int)($img_meta['sizes'][$size]['width'] ?? $img_meta['width']),
                'height' => (int)($img_meta['sizes'][$size]['height'] ?? $img_meta['height']),
            ];
        }
    }

    return [
        'sizes' => $sizes_dimensions,
        'urls' => $sizes_urls,
        'alt' => get_post_meta($img_id, '_wp_attachment_image_alt', true) ?: '',
        'title' => get_the_title($img_id) ?: '',
        'type' => $img_type,
    ];
}

// In-memory cache for metadata within the same request
$GLOBALS['img_metadata_cache'] = [];

function img_get_fields(array|string $img, bool $is_webp = false): array
{
    // Generate cache key and return cached metadata when available
    $cache_key = is_array($img) ? md5(serialize($img)) : md5($img . ($is_webp ? '_w' : ''));
    if (isset($GLOBALS['img_metadata_cache'][$cache_key])) {
        return $GLOBALS['img_metadata_cache'][$cache_key];
    }

    if (is_array($img) && isset($img['sizes'])) {
        $result = img_parse_acf_image($img);
    } else {
        $result = img_parse_url_image($img, $is_webp);
    }

    $GLOBALS['img_metadata_cache'][$cache_key] = $result;
    return $result;
}

// WebP presence check (cache per URL)
function img_evaluate_webp(string $img_url): bool
{
    static $webp_cache = [];

    if (isset($webp_cache[$img_url])) {
        return $webp_cache[$img_url];
    }

    $file_path = str_replace(home_url(), ABSPATH, $img_url) . '.webp';
    $exists = file_exists($file_path);

    $webp_cache[$img_url] = $exists;
    return $exists;
}

/**
 * Prepare attributes for <img>
 */
function img_prepare_attributes(
    string $id,
    string $classes,
    string $alt_text,
    string $fallback_alt,
    string $img_attr,
    bool $is_priority
): array {
    return [
        'id' => $id ? esc_attr($id) : '',
        'class' => $classes ? esc_attr($classes) : '',
        'alt' => esc_attr($alt_text ?: $fallback_alt),
        'loading' => $is_priority ? 'eager' : 'lazy',
        'fetchpriority' => $is_priority ? 'high' : 'auto',
        'decoding' => 'async',
        'extra' => $img_attr ? ' ' . wp_kses_post($img_attr) : '',
    ];
}

/**
 * Main function: generates responsive <picture> tag with WebP and breakpoint support
 * Returns HTML string (does not print directly)
 */
function img_generate_picture_tag(
    array|string $img,
    array|string $mobile_img = [],
    array|string $tablet_img = [],
    string $max_size = 'full',
    string $classes = '',
    string $id = '',
    string $alt_text = '',
    bool $is_cover = false,
    string $img_attr = '',
    bool $is_priority = false
): string {

    if (empty($img)) {
        return '';
    }

    // Ensure sizes initialized (in case called early)
    if (empty($GLOBALS['sizes'])) {
        po_init_sizes();
    }

    // Validate requested size: fall back to 'full' if not registered
    if (!in_array($max_size, $GLOBALS['sizes'], true)) {
        $max_size = 'full';
    }

    $img_fields = img_get_fields($img);

    // SVG handling (reuse image_to_svg if available)
    if (in_array($img_fields['type'], ['image/svg+xml', 'image/svg'], true)) {
        if (function_exists('image_to_svg')) {
            return image_to_svg($img);
        }
        return '';
    }

    // WebP fields (if .webp for full exists)
    $img_webp_fields = img_evaluate_webp($img_fields['urls']['full'])
        ? img_get_fields($img_fields['urls']['full'], true)
        : null;

    $attrs = img_prepare_attributes($id, $classes, $alt_text, $img_fields['alt'], $img_attr, $is_priority);

    // Shortcut for thumbnail size
    if ($max_size === 'thumbnail') {
        $sources = [];
        if ($img_webp_fields) {
            $sources[] = img_create_source_tag($img_webp_fields['urls']['thumbnail'], $img_webp_fields['type']);
        }
        $img_tag = img_create_img_tag(
            $img_fields['urls']['thumbnail'],
            $img_fields['sizes']['thumbnail']['width'] ?? 0,
            $img_fields['sizes']['thumbnail']['height'] ?? 0,
            $attrs
        );
        return img_wrap_picture($sources, $img_tag, $attrs);
    }

    // NEW: Auto-detect cover sizes when is_cover=true and no mobile/tablet provided
    if ($is_cover && empty($tablet_img) && empty($mobile_img)) {
        $cover_sizes = po_detect_cover_sizes();

        if (!empty($cover_sizes)) {
            $sources = [];
            $smallest_cover = null;

            // Generate sources for each cover size (from largest to smallest)
            foreach ($cover_sizes as $index => $cover_info) {
                $size_name = $cover_info['name'];
                $breakpoint = $cover_info['breakpoint'];


                // Store the smallest cover for the final <img> tag
                if ($smallest_cover === null || $cover_info['width'] < $smallest_cover['width']) {
                    // Ensure it's at least cover-mobile level
                    if (strpos(strtolower($size_name), 'mobile') !== false || $breakpoint === 'mobile') {
                        $smallest_cover = $cover_info;
                    }
                }

                // Don't add media query for the smallest breakpoint (mobile)
                $media = ($breakpoint === 'mobile') ? null : "(min-width: {$GLOBALS['breakpoints'][$breakpoint]})";

                // Add WebP source if available
                if (!empty($img_fields['urls'][$size_name])) {
                    if (img_evaluate_webp($img_fields['urls'][$size_name])) {
                        $webp_fields = img_get_fields($img_fields['urls'][$size_name], true);
                        if ($webp_fields && !empty($webp_fields['urls'][$size_name])) {
                            $sources[] = img_create_source_tag(
                                $webp_fields['urls'][$size_name],
                                $webp_fields['type'],
                                $media
                            );
                        }
                    }

                    // Add original source
                    $sources[] = img_create_source_tag(
                        $img_fields['urls'][$size_name],
                        $img_fields['type'],
                        $media
                    );
                }
            }

            // Use smallest cover size for <img> fallback (or cover-mobile if detected)
            $fallback_size = $smallest_cover ? $smallest_cover['name'] : 'cover-mobile';

            // If fallback size doesn't exist in urls, use 'full'
            if (empty($img_fields['urls'][$fallback_size])) {
                $fallback_size = 'full';
            }

            $img_tag = img_create_img_tag(
                $img_fields['urls'][$fallback_size],
                $img_fields['sizes'][$fallback_size]['width'] ?? 0,
                $img_fields['sizes'][$fallback_size]['height'] ?? 0,
                $attrs
            );

            return img_wrap_picture($sources, $img_tag, $attrs);
        }

        // Fallback if no cover sizes detected: simple picture with max_size
        $sources = [];
        if ($img_webp_fields) {
            $sources[] = img_create_source_tag($img_webp_fields['urls'][$max_size] ?? $img_webp_fields['urls']['full'], $img_webp_fields['type']);
        }
        $img_tag = img_create_img_tag(
            $img_fields['urls'][$max_size] ?? $img_fields['urls']['full'],
            $img_fields['sizes'][$max_size]['width'] ?? 0,
            $img_fields['sizes'][$max_size]['height'] ?? 0,
            $attrs
        );
        return img_wrap_picture($sources, $img_tag, $attrs);
    }

    // Build sources by breakpoint: iterate from largest -> smallest for progressive rules
    $order = ['hdpi', 'mdpi', 'ldpi', 'tablet', 'mobile'];
    $sources = [];

    foreach ($order as $bp) {
        $media = $bp === 'mobile' ? null : "(min-width: {$GLOBALS['breakpoints'][$bp]})";
        // If user provided explicit tablet/mobile images, use them
        if ($bp === 'tablet' && !empty($tablet_img)) {
            $device_fields = img_get_fields($tablet_img);
            $device_webp = img_evaluate_webp($device_fields['urls']['full']) ? img_get_fields($device_fields['urls']['full'], true) : null;
            if ($device_webp) {
                $sources[] = img_create_source_tag($device_webp['urls']['full'], $device_webp['type'], $media);
            }
            $sources[] = img_create_source_tag($device_fields['urls']['full'], $device_fields['type'], $media);
            continue;
        }
        if ($bp === 'mobile' && !empty($mobile_img)) {
            $device_fields = img_get_fields($mobile_img);
            $device_webp = img_evaluate_webp($device_fields['urls']['full']) ? img_get_fields($device_fields['urls']['full'], true) : null;
            if ($device_webp) {
                $sources[] = img_create_source_tag($device_webp['urls']['full'], $device_webp['type'], $media);
            }
            $sources[] = img_create_source_tag($device_fields['urls']['full'], $device_fields['type'], $media);
            continue;
        }

        // choose preferred size for this breakpoint
        $preferred = po_get_preferred_size_for_breakpoint($bp);
        if (!$preferred) {
            continue;
        }

        // WebP for that preferred size
        if ($img_webp_fields && !empty($img_webp_fields['urls'][$preferred])) {
            $sources[] = img_create_source_tag($img_webp_fields['urls'][$preferred], $img_webp_fields['type'], $media);
        } elseif (img_evaluate_webp($img_fields['urls'][$preferred] ?? $img_fields['urls']['full'])) {
            $webp_fields = img_get_fields($img_fields['urls'][$preferred] ?? $img_fields['urls']['full'], true);
            if ($webp_fields) {
                $sources[] = img_create_source_tag($webp_fields['urls']['full'] ?? $webp_fields['urls'][$preferred], $webp_fields['type'], $media);
            }
        }

        // original source for that preferred size
        if (!empty($img_fields['urls'][$preferred])) {
            $sources[] = img_create_source_tag($img_fields['urls'][$preferred], $img_fields['type'], $media);
        }
    }

    // If user passed explicit mobile_img, use it as final <img> fallback
    if (!empty($mobile_img)) {
        $mobile_fields = img_get_fields($mobile_img);
        $img_tag = img_create_img_tag(
            $mobile_fields['urls']['full'],
            $mobile_fields['sizes']['full']['width'] ?? 0,
            $mobile_fields['sizes']['full']['height'] ?? 0,
            $attrs
        );
        return img_wrap_picture($sources, $img_tag, $attrs);
    }

    // fallback: choose requested size or 'medium' when not full
    $fallback_size = ($max_size === 'full' || $is_cover) ? $max_size : (in_array('medium', $GLOBALS['sizes'], true) ? 'medium' : 'full');

    $img_tag = img_create_img_tag(
        $img_fields['urls'][$fallback_size] ?? $img_fields['urls']['full'],
        $img_fields['sizes'][$fallback_size]['width'] ?? 0,
        $img_fields['sizes'][$fallback_size]['height'] ?? 0,
        $attrs
    );

    return img_wrap_picture($sources, $img_tag, $attrs);
}

/**
 * Helper function to print the picture tag directly
 */
function img_print_picture_tag(
    array|string $img,
    array|string $mobile_img = [],
    array|string $tablet_img = [],
    string $max_size = 'full',
    string $classes = '',
    string $id = '',
    string $alt_text = '',
    bool $is_cover = false,
    string $img_attr = '',
    bool $is_priority = false
): void {
    echo img_generate_picture_tag(
        $img,
        $mobile_img,
        $tablet_img,
        $max_size,
        $classes,
        $id,
        $alt_text,
        $is_cover,
        $img_attr,
        $is_priority
    );
}
