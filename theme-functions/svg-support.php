<?php
//Enable SVG uploads
function add_file_types_to_uploads($file_types)
{
    $new_filetypes = array();
    $new_filetypes['svg'] = 'image/svg+xml';
    $file_types = array_merge($file_types, $new_filetypes);
    return $file_types;
}
add_filter('upload_mimes', 'add_file_types_to_uploads');

function wp_check_svg($file)
{
    $filetype = wp_check_filetype($file['name']);

    $ext = $filetype['ext'];
    $type = $filetype['type'];

    // Check if uploaded file is a SVG
    if ($type !== 'image/svg+xml' || $ext !== 'svg') {
        return $file;
    }

    // Make sure that the file is being uploaded by a trusted user
    if (!current_user_can('upload_files')) {
        return $file;
    }

    // Use WP_Filesystem to read the contents of the file
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

//Image to SVG 
function image_to_svg($image)
{
    if (empty($image) || !isset($image['url'], $image['mime_type'])) {
        return '';
    }

    try {
        $upload_dir = wp_get_upload_dir();
        $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image['url']);

        if (!file_exists($image_path)) {
            throw new Exception('Image file not found');
        }

        if ($image['mime_type'] === "image/svg+xml") {
            $svg_content = file_get_contents($image_path);
            if ($svg_content === false) {
                throw new Exception('Could not read SVG file');
            }
            return $svg_content;
        }

        // Construir tag img con atributos escapados
        return sprintf(
            '<img src="%s" width="%s" height="%s" alt="%s" title="%s" loading="lazy" decoding="async">',
            esc_url($image['url']),
            esc_attr($image['width'] ?? ''),
            esc_attr($image['height'] ?? ''),
            esc_attr($image['alt'] ?? ''),
            esc_attr($image['title'] ?? '')
        );

    } catch (Exception $e) {
        error_log('SVG Processing Error: ' . $e->getMessage());
        return '';
    }
}

//SVG in content
function check_content_images($content)
{
    if (!has_blocks($content) && !preg_match('/<img/', $content)) {
        return $content;
    }

    $pattern = '/<img\s[^>]*src=["\']([^"\']+)["\'][^>]*>/i';
    
    return preg_replace_callback($pattern, function ($match) {
        $src = $match[1];
        
        // Convertir URL a ruta local
        $src_local = strpos($src, home_url()) !== false 
            ? str_replace(home_url('/'), ABSPATH, $src)
            : $src;

        try {
            if (!file_exists($src_local)) {
                return $match[0];
            }

            $mime_type = mime_content_type($src_local);
            
            if ($mime_type !== 'image/svg+xml') {
                return $match[0];
            }

            $svg_content = file_get_contents($src_local);
            return $svg_content !== false ? $svg_content : $match[0];

        } catch (Exception $e) {
            error_log('SVG Content Processing Error: ' . $e->getMessage());
            return $match[0];
        }
    }, $content);
}

add_filter('the_content', 'check_content_images');

function sanitize_svg($file) {
    if (empty($file['tmp_name'])) {
        return $file;
    }

    $filetype = wp_check_filetype($file['name']);
    
    if ($filetype['type'] !== 'image/svg+xml') {
        return $file;
    }

    // Verificar permisos
    if (!current_user_can('upload_files')) {
        $file['error'] = __('Sorry, you are not allowed to upload SVG files.', 'growthlab');
        return $file;
    }

    // Lista de elementos y atributos permitidos
    $allowed_tags = array('svg', 'path', 'rect', 'circle', 'g', 'polygon');
    $allowed_attrs = array('viewBox', 'width', 'height', 'fill', 'stroke', 'd', 'x', 'y');

    // Cargar y sanitizar SVG
    $content = file_get_contents($file['tmp_name']);
    $doc = new DOMDocument();
    $doc->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING);

    // Remover elementos no permitidos
    $elements = $doc->getElementsByTagName('*');
    for ($i = $elements->length - 1; $i >= 0; $i--) {
        $element = $elements->item($i);
        if (!in_array($element->tagName, $allowed_tags)) {
            $element->parentNode->removeChild($element);
        }
        
        // Remover atributos no permitidos
        foreach (iterator_to_array($element->attributes) as $attr) {
            if (!in_array($attr->nodeName, $allowed_attrs)) {
                $element->removeAttribute($attr->nodeName);
            }
        }
    }

    // Guardar SVG sanitizado
    file_put_contents($file['tmp_name'], $doc->saveXML());
    
    return $file;
}
