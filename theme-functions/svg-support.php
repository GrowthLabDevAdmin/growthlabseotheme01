<?php
if (!function_exists('add_file_types_to_uploads')) {
    function add_file_types_to_uploads($file_types)
    {
        $new_filetypes = array();
        $new_filetypes['svg'] = 'image/svg+xml';
        return array_merge($file_types, $new_filetypes);
    }
}
add_filter('upload_mimes', 'add_file_types_to_uploads');

add_filter('wp_check_filetype_and_ext', 'allow_svg_filetype', 10, 5);
function allow_svg_filetype($data, $file, $filename, $mimes, $real_mime)
{
    if (preg_match('/\.svg$/i', $filename)) {
        $data[0] = 'svg';
        $data[1] = 'image/svg+xml';
        $data[2] = false;
        $data[3] = 'svg';
    }

    return $data;
}

add_filter('file_is_displayable_image', 'allow_svg_as_displayable_image', 10, 2);
function allow_svg_as_displayable_image($result, $path)
{
    if ($result === false && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
        return true;
    }

    return $result;
}

if (!function_exists('wp_check_svg')) {
    function wp_check_svg($file)
    {
        $filetype = wp_check_filetype($file['name']);
        $ext = $filetype['ext'];
        $type = $filetype['type'];

        if ($type !== 'image/svg+xml' || $ext !== 'svg') {
            return $file;
        }

        if (!current_user_can('upload_files')) {
            return $file;
        }

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $content = $wp_filesystem->get_contents($file['tmp_name']);
        $doc = new DOMDocument();
        $doc->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);

        $scripts = $doc->getElementsByTagName('script');
        if ($scripts->length > 0) {
            return $file;
        }

        return $file;
    }
}
add_filter('wp_handle_upload_prefilter', 'wp_check_svg');

if (!function_exists('image_to_svg')) {
    function image_to_svg(array|string $image, string $classes = '')
    {
        if (is_array($image) && (empty($image) || !isset($image['url'], $image['mime_type']))) {
            return '';
        }

        if (is_string($image) && $image === '') {
            return '';
        }

        $img_url = is_array($image) ? $image['url'] : $image;

        try {
            $upload_dir = wp_get_upload_dir();
            $img_url = preg_replace('/\?.*$/', '', $img_url);

            $baseurl = untrailingslashit($upload_dir['baseurl']);
            $basedir = untrailingslashit($upload_dir['basedir']);

            if (strpos($img_url, $baseurl) !== false) {
                $image_path = str_replace($baseurl, $basedir, $img_url);
            } elseif (strpos($img_url, home_url('/')) !== false) {
                $image_path = str_replace(home_url('/'), ABSPATH, $img_url);
            } else {
                $image_path = $img_url;
            }

            $image_path = wp_normalize_path($image_path);

            if (!file_exists($image_path) || !is_readable($image_path)) {
                return '';
            }

            if (mime_content_type($image_path) === 'image/svg+xml') {
                $svg_content = @file_get_contents($image_path);
                if ($svg_content === false) {
                    return '';
                }

                return sprintf('<div class="%s">%s</div>', esc_attr($classes), $svg_content);
            }

            return sprintf(
                '<img src="%s" width="%s" height="%s" alt="%s" title="%s" loading="lazy" decoding="async">',
                esc_url($img_url),
                esc_attr($image['width'] ?? ''),
                esc_attr($image['height'] ?? ''),
                esc_attr($image['alt'] ?? ''),
                esc_attr($image['title'] ?? '')
            );
        } catch (Exception $e) {
            return '';
        }
    }
}

if (!function_exists('check_content_images')) {
    function check_content_images($content)
    {
        if (strpos($content, '<img') === false) {
            return $content;
        }

        $pattern = '/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/i';
        $matches = [];

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
            return $content;
        }

        if (count($matches[0]) > 20) {
            return $content;
        }

        return preg_replace_callback($pattern, function ($match) {
            $src = $match[1];

            if (strpos($src, '.svg') === false) {
                return $match[0];
            }

            $upload_dir = wp_get_upload_dir();
            $src_trim = preg_replace('/\?.*$/', '', $src);

            if (strpos($src_trim, untrailingslashit($upload_dir['baseurl'])) !== false) {
                $src_local = str_replace(untrailingslashit($upload_dir['baseurl']), untrailingslashit($upload_dir['basedir']), $src_trim);
            } elseif (strpos($src_trim, home_url('/')) !== false) {
                $src_local = str_replace(home_url('/'), ABSPATH, $src_trim);
            } else {
                return $match[0];
            }

            $src_local = wp_normalize_path($src_local);

            try {
                if (!file_exists($src_local) || !is_readable($src_local)) {
                    return $match[0];
                }

                if (pathinfo($src_local, PATHINFO_EXTENSION) !== 'svg') {
                    return $match[0];
                }

                $svg_content = file_get_contents($src_local);
                return $svg_content !== false ? $svg_content : $match[0];
            } catch (Exception $e) {
                return $match[0];
            }
        }, $content);
    }
}
add_filter('the_content', 'check_content_images', 20);

if (!function_exists('sanitize_svg')) {
    function sanitize_svg($file)
    {
        if (empty($file['tmp_name'])) {
            return $file;
        }

        $filetype = wp_check_filetype($file['name']);
        if ($filetype['type'] !== 'image/svg+xml') {
            return $file;
        }

        if (!current_user_can('upload_files')) {
            $file['error'] = __('Sorry, you are not allowed to upload SVG files.', 'growthlab');
            return $file;
        }

        $allowed_tags = array('svg', 'path', 'rect', 'circle', 'g', 'polygon');
        $allowed_attrs = array('viewBox', 'width', 'height', 'fill', 'stroke', 'd', 'x', 'y');

        $content = file_get_contents($file['tmp_name']);
        $doc = new DOMDocument();
        $doc->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);

        $elements = $doc->getElementsByTagName('*');
        for ($i = $elements->length - 1; $i >= 0; $i--) {
            $element = $elements->item($i);
            if (!in_array($element->tagName, $allowed_tags)) {
                $element->parentNode->removeChild($element);
            }

            foreach (iterator_to_array($element->attributes) as $attr) {
                if (!in_array($attr->nodeName, $allowed_attrs)) {
                    $element->removeAttribute($attr->nodeName);
                }
            }
        }

        file_put_contents($file['tmp_name'], $doc->saveXML());
        return $file;
    }
}
