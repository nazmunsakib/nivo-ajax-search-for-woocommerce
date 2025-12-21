<?php
/**
 * Search Preset Custom Post Type
 *
 * @package NivoSearch
 * @since 1.1.0
 */

namespace NivoSearch;

defined('ABSPATH') || exit;

/**
 * Search Preset CPT Class
 *
 * @since 1.1.0
 */
class Search_Preset_CPT {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_nivo_search_preset', [$this, 'save_preset_meta'], 10, 2);
        add_filter('manage_nivo_search_preset_posts_columns', [$this, 'set_columns']);
        add_action('manage_nivo_search_preset_posts_custom_column', [$this, 'render_columns'], 10, 2);
    }

    /**
     * Register custom post type
     */
    public function register_post_type() {
        register_post_type('nivo_search_preset', [
            'labels' => [
                'name' => __('Search Presets', 'nivo-ajax-search-for-woocommerce'),
                'singular_name' => __('Search Preset', 'nivo-ajax-search-for-woocommerce'),
                'add_new' => __('Add New Preset', 'nivo-ajax-search-for-woocommerce'),
                'add_new_item' => __('Add New Search Preset', 'nivo-ajax-search-for-woocommerce'),
                'edit_item' => __('Edit Search Preset', 'nivo-ajax-search-for-woocommerce'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'nivo-search',
            'menu_icon' => 'dashicons-search',
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'manage_options',
            ],
            'map_meta_cap' => true,
        ]);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'nivo_search_shortcode',
            __('Shortcode', 'nivo-ajax-search-for-woocommerce'),
            [$this, 'render_shortcode_box'],
            'nivo_search_preset',
            'side',
            'high'
        );

        add_meta_box(
            'nivo_search_settings',
            __('Search Settings', 'nivo-ajax-search-for-woocommerce'),
            [$this, 'render_settings_box'],
            'nivo_search_preset',
            'normal',
            'high'
        );
    }

    /**
     * Render shortcode meta box
     */
    public function render_shortcode_box($post) {
        if ($post->post_status === 'publish') {
            $shortcode = '[nivo_search id="' . $post->ID . '"]';
            ?>
            <div class="nivo-shortcode-box">
                <p style="margin:0 0 10px;font-weight:600;color:#1d2327;"><?php echo esc_html($post->post_title); ?></p>
                <input type="text" readonly value="<?php echo esc_attr($shortcode); ?>" 
                       onclick="this.select();" style="width:100%;padding:8px;font-family:monospace;">
                <button type="button" class="button button-secondary" style="width:100%;margin-top:10px;" 
                        onclick="navigator.clipboard.writeText('<?php echo esc_js($shortcode); ?>');this.innerText='Copied!';setTimeout(()=>this.innerText='Copy Shortcode',2000);">
                    <?php _e('Copy Shortcode', 'nivo-ajax-search-for-woocommerce'); ?>
                </button>
            </div>
            <?php
        } else {
            echo '<p>' . __('Publish to generate shortcode', 'nivo-ajax-search-for-woocommerce') . '</p>';
        }
    }

    /**
     * Render settings meta box
     */
    public function render_settings_box($post) {
        wp_nonce_field('nivo_preset_meta', 'nivo_preset_nonce');
        
        $settings = get_post_meta($post->ID, '_nivo_search_settings', true);
        $defaults = [
            'limit' => 10,
            'min_chars' => 2,
            'placeholder' => 'Search products...',
            'search_in_title' => 1,
            'search_in_sku' => 1,
            'search_in_content' => 0,
            'search_in_excerpt' => 0,
            'search_in_categories' => 0,
            'search_in_tags' => 0,
            'exclude_out_of_stock' => 0,
            'show_images' => 1,
            'show_price' => 1,
            'show_sku' => 0,
            'show_description' => 0,
            'bar_width' => 600,
            'bar_height' => 50,
            'border_width' => 1,
            'border_color' => '#ddd',
            'border_radius' => 5,
            'bg_color' => '#ffffff',
            'text_color' => '#333333',
            'results_border_width' => 1,
            'results_border_color' => '#ddd',
            'results_border_radius' => 4,
            'results_bg_color' => '#ffffff',
            'results_padding' => 10,
        ];
        $settings = wp_parse_args($settings, $defaults);
        ?>
        <style>
            .nivo-settings-section { margin-bottom: 25px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
            .nivo-settings-section h3 { margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .nivo-setting-row { margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
            .nivo-setting-row label { min-width: 180px; font-weight: 600; }
            .nivo-setting-row input[type="number"],
            .nivo-setting-row input[type="text"] { width: 200px; }
            .nivo-setting-row input[type="color"] { width: 80px; height: 35px; }
        </style>

        <!-- General Settings -->
        <div class="nivo-settings-section">
            <h3><?php _e('General Settings', 'nivo-ajax-search-for-woocommerce'); ?></h3>
            
            <div class="nivo-setting-row">
                <label><?php _e('Results Limit', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[limit]" value="<?php echo esc_attr($settings['limit']); ?>" min="1" max="50">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Minimum Characters', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[min_chars]" value="<?php echo esc_attr($settings['min_chars']); ?>" min="1" max="5">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Placeholder Text', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="text" name="nivo_settings[placeholder]" value="<?php echo esc_attr($settings['placeholder']); ?>">
            </div>
        </div>

        <!-- Search Scope -->
        <div class="nivo-settings-section">
            <h3><?php _e('Search Scope', 'nivo-ajax-search-for-woocommerce'); ?></h3>
            
            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[search_in_title]" value="1" <?php checked($settings['search_in_title'], 1); ?>>
                    <?php _e('Search in Title', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>

            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[search_in_sku]" value="1" <?php checked($settings['search_in_sku'], 1); ?>>
                    <?php _e('Search in SKU', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>

            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[search_in_content]" value="1" <?php checked($settings['search_in_content'], 1); ?>>
                    <?php _e('Search in Description', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>

            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[search_in_excerpt]" value="1" <?php checked($settings['search_in_excerpt'], 1); ?>>
                    <?php _e('Search in Short Description', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>

            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[exclude_out_of_stock]" value="1" <?php checked($settings['exclude_out_of_stock'], 1); ?>>
                    <?php _e('Exclude Out of Stock', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>
        </div>

        <!-- Others Content -->
        <div class="nivo-settings-section">
            <h3><?php _e('Others Content', 'nivo-ajax-search-for-woocommerce'); ?></h3>
            
            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[search_in_categories]" value="1" <?php checked($settings['search_in_categories'], 1); ?>>
                    <?php _e('Show Categories', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>

            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[search_in_tags]" value="1" <?php checked($settings['search_in_tags'], 1); ?>>
                    <?php _e('Show Tags', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>
        </div>

        <!-- Display Options -->
        <div class="nivo-settings-section">
            <h3><?php _e('Display Options', 'nivo-ajax-search-for-woocommerce'); ?></h3>
            
            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[show_images]" value="1" <?php checked($settings['show_images'], 1); ?>>
                    <?php _e('Show Product Images', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>

            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[show_price]" value="1" <?php checked($settings['show_price'], 1); ?>>
                    <?php _e('Show Price', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>

            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[show_sku]" value="1" <?php checked($settings['show_sku'], 1); ?>>
                    <?php _e('Show SKU', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>

            <div class="nivo-setting-row">
                <label>
                    <input type="checkbox" name="nivo_settings[show_description]" value="1" <?php checked($settings['show_description'], 1); ?>>
                    <?php _e('Show Short Description', 'nivo-ajax-search-for-woocommerce'); ?>
                </label>
            </div>
        </div>

        <!-- Search Bar Styling -->
        <div class="nivo-settings-section">
            <h3><?php _e('Search Bar Styling', 'nivo-ajax-search-for-woocommerce'); ?></h3>
            
            <div class="nivo-setting-row">
                <label><?php _e('Width (px)', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[bar_width]" value="<?php echo esc_attr($settings['bar_width']); ?>" min="200" max="1200">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Height (px)', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[bar_height]" value="<?php echo esc_attr($settings['bar_height']); ?>" min="30" max="100">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Border Width (px)', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[border_width]" value="<?php echo esc_attr($settings['border_width']); ?>" min="0" max="10">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Border Color', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="color" name="nivo_settings[border_color]" value="<?php echo esc_attr($settings['border_color']); ?>">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Border Radius (px)', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[border_radius]" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="50">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Background Color', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="color" name="nivo_settings[bg_color]" value="<?php echo esc_attr($settings['bg_color']); ?>">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Text Color', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="color" name="nivo_settings[text_color]" value="<?php echo esc_attr($settings['text_color']); ?>">
            </div>
        </div>

        <!-- Results Styling -->
        <div class="nivo-settings-section">
            <h3><?php _e('Results Styling', 'nivo-ajax-search-for-woocommerce'); ?></h3>
            
            <div class="nivo-setting-row">
                <label><?php _e('Border Width (px)', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[results_border_width]" value="<?php echo esc_attr($settings['results_border_width']); ?>" min="0" max="10">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Border Color', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="color" name="nivo_settings[results_border_color]" value="<?php echo esc_attr($settings['results_border_color']); ?>">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Border Radius (px)', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[results_border_radius]" value="<?php echo esc_attr($settings['results_border_radius']); ?>" min="0" max="50">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Background Color', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="color" name="nivo_settings[results_bg_color]" value="<?php echo esc_attr($settings['results_bg_color']); ?>">
            </div>

            <div class="nivo-setting-row">
                <label><?php _e('Padding (px)', 'nivo-ajax-search-for-woocommerce'); ?></label>
                <input type="number" name="nivo_settings[results_padding]" value="<?php echo esc_attr($settings['results_padding']); ?>" min="0" max="50">
            </div>
        </div>
        <?php
    }

    /**
     * Save preset meta
     */
    public function save_preset_meta($post_id, $post) {
        if (!isset($_POST['nivo_preset_nonce']) || !wp_verify_nonce($_POST['nivo_preset_nonce'], 'nivo_preset_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['nivo_settings'])) {
            $settings = $_POST['nivo_settings'];
            
            // Sanitize settings
            $sanitized = [
                'limit' => absint($settings['limit'] ?? 10),
                'min_chars' => absint($settings['min_chars'] ?? 2),
                'placeholder' => sanitize_text_field($settings['placeholder'] ?? ''),
                'search_in_title' => isset($settings['search_in_title']) ? 1 : 0,
                'search_in_sku' => isset($settings['search_in_sku']) ? 1 : 0,
                'search_in_content' => isset($settings['search_in_content']) ? 1 : 0,
                'search_in_excerpt' => isset($settings['search_in_excerpt']) ? 1 : 0,
                'search_in_categories' => isset($settings['search_in_categories']) ? 1 : 0,
                'search_in_tags' => isset($settings['search_in_tags']) ? 1 : 0,
                'exclude_out_of_stock' => isset($settings['exclude_out_of_stock']) ? 1 : 0,
                'show_images' => isset($settings['show_images']) ? 1 : 0,
                'show_price' => isset($settings['show_price']) ? 1 : 0,
                'show_sku' => isset($settings['show_sku']) ? 1 : 0,
                'show_description' => isset($settings['show_description']) ? 1 : 0,
                'bar_width' => absint($settings['bar_width'] ?? 600),
                'bar_height' => absint($settings['bar_height'] ?? 50),
                'border_width' => absint($settings['border_width'] ?? 1),
                'border_color' => sanitize_hex_color($settings['border_color'] ?? '#ddd'),
                'border_radius' => absint($settings['border_radius'] ?? 5),
                'bg_color' => sanitize_hex_color($settings['bg_color'] ?? '#ffffff'),
                'text_color' => sanitize_hex_color($settings['text_color'] ?? '#333333'),
                'results_border_width' => absint($settings['results_border_width'] ?? 1),
                'results_border_color' => sanitize_hex_color($settings['results_border_color'] ?? '#ddd'),
                'results_border_radius' => absint($settings['results_border_radius'] ?? 4),
                'results_bg_color' => sanitize_hex_color($settings['results_bg_color'] ?? '#ffffff'),
                'results_padding' => absint($settings['results_padding'] ?? 10),
            ];

            update_post_meta($post_id, '_nivo_search_settings', $sanitized);
        }
    }

    /**
     * Set custom columns
     */
    public function set_columns($columns) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['shortcode'] = __('Shortcode', 'nivo-ajax-search-for-woocommerce');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom columns
     */
    public function render_columns($column, $post_id) {
        if ($column === 'shortcode') {
            $shortcode = '[nivo_search id="' . $post_id . '"]';
            echo '<code style="background:#f0f0f0;padding:4px 8px;border-radius:3px;">' . esc_html($shortcode) . '</code>';
        }
    }
}
