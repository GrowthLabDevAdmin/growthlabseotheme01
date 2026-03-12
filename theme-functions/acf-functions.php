<?php
/* ACF Functions
-------------------------------------------------------------- */

// Custom Block Categories
function growthlabtheme01_blocks_category($categories, $post)
{
    return array_merge(
        $categories,
        array(
            array(
                'slug'  => 'growthlabtheme01-blocks',
                'title' => __('Growthlab Theme 01 Blocks', 'growthlabtheme01-blocks'),
            )
        )
    );
}
add_filter('block_categories_all', 'growthlabtheme01_blocks_category', 10, 2);

// Register Block Types with caching to prevent memory bloat during cache flushes
function register_acf_blocks()
{
    // Use transient to avoid repeated filesystem operations during W3TC cache flushes
    $block_files = get_transient('growthlabtheme01_block_files_cache');

    if ($block_files === false) {
        $block_files = [];

        // 1. Parent blocks
        foreach (glob(get_template_directory() . '/blocks/*/block.json') ?: [] as $block) {
            $data = json_decode(file_get_contents($block), true);

            if (!empty($data['name'])) {
                $block_files[$data['name']] = dirname($block);
            }
        }

        // 2. Child blocks (override)
        foreach (glob(get_stylesheet_directory() . '/blocks/*/block.json') ?: [] as $block) {
            $data = json_decode(file_get_contents($block), true);

            if (!empty($data['name'])) {
                $block_files[$data['name']] = dirname($block);
            }
        }

        // Cache for 24 hours, purged on theme update
        set_transient('growthlabtheme01_block_files_cache', $block_files, 24 * HOUR_IN_SECONDS);
    }

    // 3. Register all found blocks
    foreach ($block_files as $block_dir) {
        register_block_type($block_dir);
    }
}

// Clear block cache on theme switch/update
add_action('switch_theme', function () {
    delete_transient('growthlabtheme01_block_files_cache');
});

add_action('init', 'register_acf_blocks', 5);


/**
 * Load block assets only when block is present on the page
 * Optimized to prevent memory bloat during cache operations
 */

// Prevent unused block assets from loading
add_filter('should_load_separate_core_block_assets', '__return_true');

/**
 * Dequeue block assets that aren't used on the current page
 * SIMPLIFIED: Only run for singular pages with blocks to avoid parse_blocks on every pageload
 */
add_action('wp_enqueue_scripts', function () {
    global $post;

    // Skip early if not a singular page or no content
    if (!is_singular() || empty($post->post_content)) {
        return;
    }

    // Skip if no blocks detected in content (string-based check, much faster)
    if (strpos($post->post_content, '<!-- wp:') === false) {
        return;
    }

    // Get all registered blocks ONCE
    $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
    if (empty($registered_blocks)) {
        return;
    }

    // Parse blocks ONLY if we detected block comments above
    $blocks = parse_blocks($post->post_content);
    $blocks_in_use = array();

    // Recursively find all blocks in use (including nested blocks)
    $find_blocks = function ($blocks) use (&$find_blocks, &$blocks_in_use) {
        foreach ($blocks as $block) {
            if (!empty($block['blockName'])) {
                $blocks_in_use[] = $block['blockName'];
            }
            if (!empty($block['innerBlocks'])) {
                $find_blocks($block['innerBlocks']);
            }
        }
    };

    $find_blocks($blocks);

    if (empty($blocks_in_use)) {
        return;
    }

    $blocks_in_use = array_unique($blocks_in_use);

    // Dequeue unused block assets - LIMIT iteration to reduce memory
    $dequeued = 0;
    foreach ($registered_blocks as $block_name => $block_type) {
        // Skip if block is in use
        if (in_array($block_name, $blocks_in_use)) {
            continue;
        }

        // Dequeue editor and frontend styles
        if (!empty($block_type->style)) {
            wp_dequeue_style($block_type->style);
        }

        if (!empty($block_type->editor_style)) {
            wp_dequeue_style($block_type->editor_style);
        }

        // Dequeue editor and frontend scripts
        if (!empty($block_type->script)) {
            wp_dequeue_script($block_type->script);
        }

        if (!empty($block_type->editor_script)) {
            wp_dequeue_script($block_type->editor_script);
        }

        if (!empty($block_type->view_script)) {
            wp_dequeue_script($block_type->view_script);
        }

        // Stop after a reasonable number to prevent excessive iterations
        $dequeued++;
        if ($dequeued > 100) {
            break;
        }
    }
}, 100);


/**
 * Move block scripts to footer (only for blocks actually in use)
 * OPTIMIZED: Only process registered block scripts, not all scripts
 */
add_action('wp_enqueue_scripts', function () {
    global $wp_scripts;

    if (empty($wp_scripts->registered)) {
        return;
    }

    $processed = 0;
    foreach ($wp_scripts->registered as $handle => $script) {
        if (
            !empty($script->src)
            && str_contains($script->src, '/blocks/')
        ) {
            // Mover al footer
            $wp_scripts->registered[$handle]->extra['group'] = 1;
            // Agregar defer
            $wp_scripts->registered[$handle]->extra['strategy'] = 'defer';

            $processed++;
            // Limit iterations to prevent excessive processing
            if ($processed > 50) {
                break;
            }
        }
    }
}, 999);

/**
 * Prevent block styles from loading in <head> for blocks not in use
 */
add_filter('render_block', function ($block_content, $block) {
    // You can add custom logic here if needed
    return $block_content;
}, 10, 2);

// Add ACF Options Page
if (function_exists('acf_add_options_page') && current_user_can('manage_options')) {
    acf_add_options_page(array(
        'page_title' => 'Site Options',
        'menu_title' => 'Site Options',
        'menu_slug' => 'site_options',
        'position' => 70,
        'capability' => 'manage_options',
        'redirect' => false
    ));
}


function my_acf_json_save_point($path)
{
    // Always save in child theme
    return get_stylesheet_directory() . '/acf-json';
}

function my_acf_json_load_point($paths)
{
    // Remove Default Path
    unset($paths[0]);

    // Parent First
    $paths[] = get_template_directory() . '/acf-json';

    // Child Override
    if (get_stylesheet_directory() !== get_template_directory()) {
        $paths[] = get_stylesheet_directory() . '/acf-json';
    }

    return $paths;
}

add_filter('acf/settings/save_json', 'my_acf_json_save_point');
add_filter('acf/settings/load_json', 'my_acf_json_load_point');


/**
 * Synchronize ACF Fields after theme updates (CI/CD Integration)
 * 
 * Importa campos ACF del tema padre automáticamente respetando:
 * - Overrides del tema hijo (si existe)
 * - Cambios realizados en el repositorio
 * - El hash MD5 de los archivos JSON para detectar cambios
 * 
 * SAFEGUARDS:
 * - Cooldown de 5 minutos entre syncs (evita loops)
 * - Timeout de 30 segundos de ejecución (evita memory bloat)
 * - Mutex DB para evitar requests paralelos
 */

function growthlabtheme01_acf_sync_run()
{
    if (!function_exists('acf_get_field_groups')) return;
    if (defined('ACF_DOING_SYNC')) return;

    $req_id = substr(md5(uniqid('', true)), 0, 6);
    error_log('[ACF sync] #' . $req_id . ' triggered by WP Pusher deploy');

    global $wpdb;
    $memory_start = memory_get_usage();

    $parent_json_path = get_template_directory() . '/acf-json';
    $child_json_path  = get_stylesheet_directory() . '/acf-json';
    $is_child_theme   = $child_json_path !== $parent_json_path;

    $parent_json_files = array_filter(
        array_merge(
            glob($parent_json_path . '/group_*.json') ?: [],
            glob($parent_json_path . '/post_type_*.json') ?: [],
            glob($parent_json_path . '/taxonomy_*.json') ?: []
        ),
        fn($f) => is_readable($f)
    );

    if (empty($parent_json_files)) {
        error_log('[ACF sync] #' . $req_id . ' aborted — no JSON files found');
        return;
    }

    try {
        // Hash para detectar si realmente hubo cambios
        $t_hash_start = microtime(true);
        $content_hash = md5(implode('', array_map('md5_file', $parent_json_files)));
        error_log('[ACF sync] #' . $req_id . ' hash computed in ' . round(microtime(true) - $t_hash_start, 3) . 's — ' . count($parent_json_files) . ' files');

        $saved_hash = $wpdb->get_var(
            "SELECT option_value FROM {$wpdb->options}
             WHERE option_name = 'acf_json_parent_sync_hash'
             LIMIT 1"
        );

        if ($saved_hash === $content_hash) {
            error_log('[ACF sync] #' . $req_id . ' skipped — hash unchanged');
            return;
        }

        error_log('[ACF sync] #' . $req_id . ' hash changed — saved: ' . substr($saved_hash ?? 'null', 0, 8) . ' | new: ' . substr($content_hash, 0, 8));

        define('ACF_DOING_SYNC', true);

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
                 VALUES ('acf_json_parent_sync_hash', %s, 'no')
                 ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
                $content_hash
            )
        );

        $total_files = count(glob($parent_json_path . '/group_*.json') ?: [])
            + count(glob($parent_json_path . '/post_type_*.json') ?: [])
            + count(glob($parent_json_path . '/taxonomy_*.json') ?: []);

        error_log('[ACF sync] #' . $req_id . ' starting | files: ' . $total_files . ' | mode: ' . ($is_child_theme ? 'child theme' : 'parent only') . ' | mem: ' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB');

        $max_execution_time = 30;
        $execution_start    = microtime(true);
        $synced             = 0;
        $skipped            = 0;
        $warnings           = 0;

        add_filter('acf/settings/save_json', '__return_false', 99);

        // ─── CPTs ─────────────────────────────────────────────────────────────
        foreach (glob($parent_json_path . '/post_type_*.json') ?: [] as $file) {
            if ((microtime(true) - $execution_start) > $max_execution_time) {
                error_log('[ACF sync] #' . $req_id . ' aborted — execution time limit reached');
                break;
            }
            if (!is_readable($file)) {
                error_log('[ACF sync] #' . $req_id . ' WARNING — CPT not readable: ' . basename($file));
                $warnings++;
                continue;
            }
            $json_data = json_decode(file_get_contents($file), true);
            if (empty($json_data) || !is_array($json_data)) {
                error_log('[ACF sync] #' . $req_id . ' WARNING — CPT JSON invalid: ' . basename($file));
                $warnings++;
                continue;
            }
            $mem_before = round(memory_get_usage() / 1024 / 1024, 2);
            acf_update_post_type($json_data);
            $mem_after = round(memory_get_usage() / 1024 / 1024, 2);
            error_log('[ACF sync] #' . $req_id . ' CPT imported: ' . ($json_data['label'] ?? basename($file)) . ' | mem: ' . $mem_before . ' -> ' . $mem_after . 'MB');
            $synced++;
        }

        // ─── Taxonomías ───────────────────────────────────────────────────────
        foreach (glob($parent_json_path . '/taxonomy_*.json') ?: [] as $file) {
            if ((microtime(true) - $execution_start) > $max_execution_time) {
                error_log('[ACF sync] #' . $req_id . ' aborted — execution time limit reached');
                break;
            }
            if (!is_readable($file)) {
                error_log('[ACF sync] #' . $req_id . ' WARNING — taxonomy not readable: ' . basename($file));
                $warnings++;
                continue;
            }
            $json_data = json_decode(file_get_contents($file), true);
            if (empty($json_data) || !is_array($json_data)) {
                error_log('[ACF sync] #' . $req_id . ' WARNING — taxonomy JSON invalid: ' . basename($file));
                $warnings++;
                continue;
            }
            $mem_before = round(memory_get_usage() / 1024 / 1024, 2);
            acf_update_taxonomy($json_data);
            $mem_after = round(memory_get_usage() / 1024 / 1024, 2);
            error_log('[ACF sync] #' . $req_id . ' taxonomy imported: ' . ($json_data['label'] ?? basename($file)) . ' | mem: ' . $mem_before . ' -> ' . $mem_after . 'MB');
            $synced++;
        }

        // ─── Field Groups ─────────────────────────────────────────────────────
        $mem_before_groups = round(memory_get_usage() / 1024 / 1024, 2);
        $t_groups_start    = microtime(true);
        $groups            = acf_get_field_groups();
        error_log(
            '[ACF sync] #' . $req_id . ' acf_get_field_groups() returned ' . count($groups) . ' groups'
                . ' | took: ' . round(microtime(true) - $t_groups_start, 3) . 's'
                . ' | mem: ' . $mem_before_groups . ' -> ' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
        );

        $rows = $wpdb->get_results(
            "SELECT MIN(ID) as ID, post_name FROM {$wpdb->posts}
             WHERE post_type = 'acf-field-group'
             AND post_status IN ('publish', 'acf-disabled', 'trash', 'draft')
             GROUP BY post_name"
        );
        $existing_ids_by_name = [];
        foreach ($rows as $r) {
            $existing_ids_by_name[$r->post_name] = (int) $r->ID;
        }
        error_log('[ACF sync] #' . $req_id . ' existing DB groups: ' . count($existing_ids_by_name));

        $processed_keys = [];

        foreach ($groups as $group) {
            if ((microtime(true) - $execution_start) > $max_execution_time) {
                error_log('[ACF sync] #' . $req_id . ' aborted — execution time limit reached');
                break;
            }

            if (empty($group['local']) || $group['local'] !== 'json') continue;
            if (in_array($group['key'], $processed_keys, true)) continue;
            $processed_keys[] = $group['key'];

            if ($is_child_theme && file_exists($child_json_path . '/' . $group['key'] . '.json')) {
                error_log('[ACF sync] #' . $req_id . ' skipped (child override): ' . ($group['title'] ?? $group['key']));
                $skipped++;
                continue;
            }

            $json_file = $parent_json_path . '/' . $group['key'] . '.json';
            if (!file_exists($json_file) || !is_readable($json_file)) {
                error_log('[ACF sync] #' . $req_id . ' WARNING — JSON not found: ' . $group['key']);
                $warnings++;
                continue;
            }

            $json_data = json_decode(file_get_contents($json_file), true);
            if (empty($json_data) || !is_array($json_data)) {
                error_log('[ACF sync] #' . $req_id . ' WARNING — JSON invalid: ' . $group['key']);
                $warnings++;
                continue;
            }

            $existing_id = $existing_ids_by_name[$group['key']] ?? 0;
            if ($existing_id) {
                $json_data['ID'] = $existing_id;
            }

            $mem_before_import = round(memory_get_usage() / 1024 / 1024, 2);
            $t_import_start    = microtime(true);
            acf_import_field_group($json_data);
            error_log(
                '[ACF sync] #' . $req_id . ' imported: ' . ($group['title'] ?? $group['key'])
                    . ' | fields: ' . count($json_data['fields'] ?? [])
                    . ' | took: ' . round(microtime(true) - $t_import_start, 3) . 's'
                    . ' | mem: ' . $mem_before_import . ' -> ' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
            );

            if (isset($json_data['active']) && $json_data['active'] === false) {
                $group_id = $existing_id ?: (acf_get_field_group($group['key'])['ID'] ?? 0);
                if ($group_id) {
                    wp_update_post(['ID' => $group_id, 'post_status' => 'acf-disabled']);
                }
                error_log('[ACF sync] #' . $req_id . ' disabled: ' . ($group['title'] ?? $group['key']));
            }

            $synced++;
        }

        // ─── Cleanup ──────────────────────────────────────────────────────────
        remove_filter('acf/settings/save_json', '__return_false', 99);

        $memory_used    = round((memory_get_usage() - $memory_start) / 1024 / 1024, 2);
        $memory_peak    = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $execution_time = round(microtime(true) - $execution_start, 2);

        $status = $warnings === 0 ? 'OK' : 'COMPLETED WITH WARNINGS';
        error_log('[ACF sync] #' . $req_id . ' ' . $status . ' — synced: ' . $synced . ', skipped: ' . $skipped . ', warnings: ' . $warnings);
        error_log('[ACF sync] #' . $req_id . ' memory: ' . $memory_used . 'MB used | ' . $memory_peak . 'MB peak | execution: ' . $execution_time . 's');
    } catch (Throwable $e) {
        remove_filter('acf/settings/save_json', '__return_false', 99);
        error_log('[ACF sync] #' . $req_id . ' ERROR — ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine());
    }
}

add_action('wppusher_theme_was_updated',   'growthlabtheme01_acf_sync_run');
add_action('wppusher_theme_was_installed', 'growthlabtheme01_acf_sync_run');

// Allow HTML in ACF fields
add_filter('acf/shortcode/allow_unsafe_html', function () {
    return true;
}, 10, 2);
add_filter('acf/the_field/allow_unsafe_html', function () {
    return true;
}, 10, 2);
add_filter('acf/the_sub_field/allow_unsafe_html', function () {
    return true;
}, 10, 2);

if (is_admin()) {
    add_filter('acf/admin/prevent_escaped_html_notice', '__return_true');
}

/**
 * ACF Color Picker Custom Palette
 * Adds custom color palette from Customizer to all ACF color picker fields
 */

/**
 * Get theme colors from Customizer
 * @return array Array of colors with hex codes and names
 */
function get_theme_color_palette_for_acf()
{
    return array(
        array(
            'name'  => 'Primary Color',
            'color' => get_theme_mod('primary_color', '#15253f'),
        ),
        array(
            'name'  => 'Primary Dark',
            'color' => get_theme_mod('primary_color_dark', '#08182f'),
        ),
        array(
            'name'  => 'Primary Light',
            'color' => get_theme_mod('primary_color_light', '#2C3D5B'),
        ),
        array(
            'name'  => 'Secondary Color',
            'color' => get_theme_mod('secondary_color', '#F4F3EE'),
        ),
        array(
            'name'  => 'Secondary Dark',
            'color' => get_theme_mod('secondary_color_dark', '#E7E5DF'),
        ),
        array(
            'name'  => 'Secondary Light',
            'color' => get_theme_mod('secondary_color_light', '#FFFFFF'),
        ),
        array(
            'name'  => 'Tertiary Color',
            'color' => get_theme_mod('tertiary_color', '#BC9061'),
        ),
        array(
            'name'  => 'Tertiary Dark',
            'color' => get_theme_mod('tertiary_color_dark', '#9D7A55'),
        ),
        array(
            'name'  => 'Tertiary Light',
            'color' => get_theme_mod('tertiary_color_light', '#DCAB77'),
        ),
        array(
            'name'  => 'Text Color',
            'color' => get_theme_mod('text_color', '#15253f'),
        ),
    );
}


/**
 * Inject color palette into ACF color picker via JavaScript
 */
function acf_color_picker_palette_script()
{
    $colors = get_theme_color_palette_for_acf();
    $palette = array();

    foreach ($colors as $color) {
        $palette[] = $color['color'];
    }

    $palette_json = json_encode($palette);
?>
    <script type="text/javascript">
        (function($) {
            if (typeof acf !== 'undefined') {
                acf.addAction('ready', function() {
                    // Override default ACF color picker settings
                    acf.add_filter('color_picker_args', function(args, $field) {
                        args.palettes = <?php echo $palette_json; ?>;
                        return args;
                    });
                });
            }
        })(jQuery);
    </script>
    <style>
        /* Style for ACF color picker palette */
        .acf-color-picker .wp-picker-container .iris-palette {
            width: 100% !important;
            max-width: 100% !important;
        }
    </style>
<?php
}
add_action('acf/input/admin_head', 'acf_color_picker_palette_script');
//add_action('acf/input/admin_footer', 'acf_color_picker_palette_script');