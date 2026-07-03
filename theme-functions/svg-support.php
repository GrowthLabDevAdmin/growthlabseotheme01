<?php
// Enable SVG uploads
function growthlab_allow_svg_uploads($file_types)
{
    $file_types['svg'] = 'image/svg+xml';
    return $file_types;
}
add_filter('upload_mimes', 'growthlab_allow_svg_uploads');

function growthlab_allow_svg_filetype($data, $file, $filename, $mimes, $real_mime)
{
    if (preg_match('/\.svg$/i', $filename) || strtolower($real_mime) === 'image/svg+xml') {
        $override = array(
            'ext' => 'svg',
            'type' => 'image/svg+xml',
            'proper_filename' => $filename,
            0 => 'svg',
            1 => 'image/svg+xml',
            2 => false,
            3 => 'svg',
        );

        $data = array_merge((array) $data, $override);
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'growthlab_allow_svg_filetype', 10, 5);

function growthlab_allow_svg_image_mime($mime, $file)
{
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg') {
        return 'image/svg+xml';
    }

    return $mime;
}
add_filter('wp_get_image_mime', 'growthlab_allow_svg_image_mime', 10, 2);

function growthlab_allow_svg_as_displayable_image($result, $path)
{
    if ($result === false && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
        return true;
    }

    return $result;
}
add_filter('file_is_displayable_image', 'growthlab_allow_svg_as_displayable_image', 10, 2);

function growthlab_get_svg_dimensions($file)
{
    if (empty($file) || !file_exists($file) || !is_readable($file)) {
        return false;
    }

    $svg_content = file_get_contents($file);
    if ($svg_content === false) {
        return false;
    }

    $width = 0;
    $height = 0;

    if (preg_match('/\bwidth=["\']?([0-9\.]+)(px)?["\']?/i', $svg_content, $match)) {
        $width = floatval($match[1]);
    }
    if (preg_match('/\bheight=["\']?([0-9\.]+)(px)?["\']?/i', $svg_content, $match)) {
        $height = floatval($match[1]);
    }

    if (($width === 0 || $height === 0) && preg_match('/viewBox=["\']?([0-9\.\s]+)["\']?/i', $svg_content, $match)) {
        $parts = preg_split('/[\s,]+/', trim($match[1]));
        if (count($parts) === 4) {
            $width = floatval($parts[2]);
            $height = floatval($parts[3]);
        }
    }

    if ($width <= 0 || $height <= 0) {
        return false;
    }

    return array($width, $height);
}

function growthlab_prepare_svg_attachment_for_js($response, $attachment, $meta)
{
    if (isset($response['mime']) && $response['mime'] === 'image/svg+xml') {
        $response['type'] = 'image';
        $response['subtype'] = 'svg+xml';
        if (empty($response['url']) && !empty($response['id'])) {
            $response['url'] = wp_get_attachment_url($response['id']);
        }

        $dimensions = false;
        if (!empty($response['id'])) {
            $file = get_attached_file($response['id']);
            $dimensions = growthlab_get_svg_dimensions($file);
        }

        if ($dimensions) {
            $response['width'] = $dimensions[0];
            $response['height'] = $dimensions[1];
            $response['sizes'] = [
                'full' => [
                    'url' => $response['url'],
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                    'orientation' => $dimensions[0] >= $dimensions[1] ? 'landscape' : 'portrait',
                ],
            ];
        } else {
            $response['sizes'] = [];
        }
    }

    return $response;
}
add_filter('wp_prepare_attachment_for_js', 'growthlab_prepare_svg_attachment_for_js', 10, 3);

function growthlab_svg_image_downsize($out, $id, $size)
{
    $mime = get_post_mime_type($id);
    if ($mime !== 'image/svg+xml') {
        return $out;
    }

    $file = get_attached_file($id);
    if (empty($file) || !file_exists($file) || !is_readable($file)) {
        return false;
    }

    $svg_content = file_get_contents($file);
    if ($svg_content === false) {
        return false;
    }

    $width = 0;
    $height = 0;
    if (preg_match('/\bwidth=["\']?([0-9\.]+)(px)?["\']?/i', $svg_content, $match)) {
        $width = floatval($match[1]);
    }
    if (preg_match('/\bheight=["\']?([0-9\.]+)(px)?["\']?/i', $svg_content, $match)) {
        $height = floatval($match[1]);
    }

    if (($width === 0 || $height === 0) && preg_match('/viewBox=["\']?([0-9\.\s]+)["\']?/i', $svg_content, $match)) {
        $parts = preg_split('/[\s,]+/', trim($match[1]));
        if (count($parts) === 4) {
            $width = floatval($parts[2]);
            $height = floatval($parts[3]);
        }
    }

    if ($width <= 0 || $height <= 0) {
        return false;
    }

    $url = wp_get_attachment_url($id);
    return array($url, $width, $height, true);
}
add_filter('image_downsize', 'growthlab_svg_image_downsize', 10, 3);

function wp_check_svg($file)
{
    $filetype = wp_check_filetype($file['name']);

    $ext = $filetype['ext'];
    $type = $filetype['type'];

    // Check if uploaded file is an SVG
    if ($type !== 'image/svg+xml' || $ext !== 'svg') {
        return $file;
    }

    // Ensure the file is uploaded by an authorized user
    if (!current_user_can('upload_files')) {
        return $file;
    }

    // Use WP_Filesystem to read the file contents
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once(ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();
    }

    $content = $wp_filesystem->get_contents($file['tmp_name']);

    // Use DOMDocument to parse the SVG file
    $doc = new DOMDocument();
    $doc->loadXML($content);

    // Check if the file contains any <script> tags
    $scripts = $doc->getElementsByTagName('script');

    if ($scripts->length > 0) {
        // The file contains <script> tags, which is not allowed
        return $file;
    }

    // The SVG file is safe, so return the original data
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'wp_check_svg');

// Image to SVG
function image_to_svg(array|string $image, string $classes = '')
{
    if (is_array($image) && (empty($image) || !isset($image['url'], $image['mime_type']))) {
        return '';
    }

    if (is_string($image) && $image === '') {
        return '';
    }

    if (is_array($image)) {
        $img_url = $image['url'];
    } else {
        $img_url = $image;
    }

    try {
        $upload_dir = wp_get_upload_dir();

        // Remove query string/URL fragments
        $img_url = preg_replace('/\?.*$/', '', $img_url);

        // Try mapping from uploads baseurl to basedir
        $baseurl = untrailingslashit($upload_dir['baseurl']);
        $basedir = untrailingslashit($upload_dir['basedir']);

        if (strpos($img_url, $baseurl) !== false) {
            $image_path = str_replace($baseurl, $basedir, $img_url);
        } elseif (strpos($img_url, home_url('/')) !== false) {
            // Fallback: map site URLs to ABSPATH
            $image_path = str_replace(home_url('/'), ABSPATH, $img_url);
        } else {
            // If not a site URL, use the path as-is (may already be local)
            $image_path = $img_url;
        }

        // Normalize path for Windows and Unix
        $image_path = wp_normalize_path($image_path);

        // Detailed debug log
        error_log(sprintf('image_to_svg: url="%s" baseurl="%s" basedir="%s" path="%s"', $img_url, $baseurl, $basedir, $image_path));

        if (!file_exists($image_path) || !is_readable($image_path)) {
            error_log('SVG Image path is missing or not readable: ' . $image_path);
            return '';
        }

        if (mime_content_type($image_path) === "image/svg+xml") {
            $svg_content = @file_get_contents($image_path);
            if ($svg_content === false) {
                error_log('Could not read SVG file: ' . $image_path);
                return '';
            }

            return "<div class='$classes'>$svg_content</div>";
        }

        // Build img tag with escaped attributes
        return sprintf(
            '<img src="%s" width="%s" height="%s" alt="%s" title="%s" loading="lazy" decoding="async">',
            esc_url($image['url']),
            esc_attr($image['width'] ?? ''),
            esc_attr($image['height'] ?? ''),
            esc_attr($image['alt'] ?? ''),
            esc_attr($image['title'] ?? ''),
        );
    } catch (Exception $e) {
        error_log('SVG Processing Error: ' . $e->getMessage());
        return '';
    }
}

// SVG in content - OPTIMIZED
function check_content_images($content)
{
    // Quick early return for content without images
    if (strpos($content, '<img') === false) {
        return $content;
    }

    $pattern = '/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/i';
    $matches = [];

    // Count matches to avoid processing too many
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
        return $content;
    }

    // Limit to first 20 images to prevent excessive processing
    if (count($matches[0]) > 20) {
        return $content;
    }

    return preg_replace_callback($pattern, function ($match) {
        $src = $match[1];

        // Quick check: skip non-SVG URLs
        if (strpos($src, '.svg') === false) {
            return $match[0];
        }

        // Convert URL to local path: remove query and map uploads
        $upload_dir = wp_get_upload_dir();
        $src_trim = preg_replace('/\?.*$/', '', $src);

        if (strpos($src_trim, untrailingslashit($upload_dir['baseurl'])) !== false) {
            $src_local = str_replace(untrailingslashit($upload_dir['baseurl']), untrailingslashit($upload_dir['basedir']), $src_trim);
        } elseif (strpos($src_trim, home_url('/')) !== false) {
            $src_local = str_replace(home_url('/'), ABSPATH, $src_trim);
        } else {
            return $match[0]; // Skip external URLs
        }

        $src_local = wp_normalize_path($src_local);

        try {
            if (!file_exists($src_local) || !is_readable($src_local)) {
                return $match[0];
            }

            // Use extension check instead of mime_content_type (faster)
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

add_filter('the_content', 'check_content_images');

function sanitize_svg($file)
{
    if (empty($file['tmp_name'])) {
        return $file;
    }

    $filetype = wp_check_filetype($file['name']);

    if ($filetype['type'] !== 'image/svg+xml') {
        return $file;
    }

    // Verify permissions
    if (!current_user_can('upload_files')) {
        $file['error'] = __('Sorry, you are not allowed to upload SVG files.', 'growthlab');
        return $file;
    }

    // Allowed elements and attributes list
    $allowed_tags = array('svg', 'path', 'rect', 'circle', 'g', 'polygon');
    $allowed_attrs = array('viewBox', 'width', 'height', 'fill', 'stroke', 'd', 'x', 'y');

    // Load and sanitize SVG
    $content = file_get_contents($file['tmp_name']);
    $doc = new DOMDocument();
    $doc->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);

    // Remove disallowed elements
    $elements = $doc->getElementsByTagName('*');
    for ($i = $elements->length - 1; $i >= 0; $i--) {
        $element = $elements->item($i);
        if (!in_array($element->tagName, $allowed_tags)) {
            $element->parentNode->removeChild($element);
        }

        // Remove disallowed attributes
        foreach (iterator_to_array($element->attributes) as $attr) {
            if (!in_array($attr->nodeName, $allowed_attrs)) {
                $element->removeAttribute($attr->nodeName);
            }
        }
    }

    // Save sanitized SVG
    file_put_contents($file['tmp_name'], $doc->saveXML());

    return $file;
}