<?php
/**
 * Shortcode Handler
 *
 * @package NivoSearch
 * @since 1.0.0
 */

namespace NivoSearch;

defined('ABSPATH') || exit;

/**
 * Shortcode Class
 *
 * Handles all shortcode functionality for the plugin
 *
 * @since 1.0.0
 */
class Shortcode {
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_shortcode('nivo_search', [$this, 'render_search_form']);
    }

    private function get_search_form_html($atts) {
        ob_start();
        ?>
        <input type="search" class="nivo-search-product-search" name="s" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" autocomplete="off">
        <div class="nivo-search-loader-icons">
            <svg class="nivo-search-close-icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                <path d="M18.3 5.71c-.39-.39-1.02-.39-1.41 0L12 10.59 7.11 5.7c-.39-.39-1.02-.39-1.41 0-.39.39-.39 1.02 0 1.41L10.59 12 5.7 16.89c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0L12 13.41l4.89 4.89c.39.39 1.02.39 1.41 0 .39-.39.39-1.02 0-1.41L13.41 12l4.89-4.89c.38-.38.38-1.02 0-1.4z"></path>
            </svg>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render search form shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_search_form($atts = []) {
        ob_start();
        
        // Check for preset ID
        $preset_id = isset($atts['id']) ? absint($atts['id']) : 0;
        $preset_settings = [];
        
        if ($preset_id && get_post_type($preset_id) === 'nivo_search_preset') {
            $preset_settings = get_post_meta($preset_id, '_nivo_search_settings', true);
            if (!is_array($preset_settings)) {
                $preset_settings = [];
            }
        }
        
        // Parse shortcode attributes with preset fallback
        $atts = shortcode_atts([
            'id' => 0,
            'placeholder' => $preset_settings['placeholder'] ?? get_option('nivo_search_placeholder_text', __('Search products...', 'nivo-ajax-search-for-woocommerce')),
            'container_class' => 'nivo-ajax-search-container',
            'input_class' => 'nivo-search-product-search',
            'results_class' => 'nivo-search-results',
            'search_bar_layout' => get_option('nivo_search_bar_layout', 1),
        ], $atts, 'nivo_search');

        $search_icon_html = '<svg class="nivosearch-ico-magnifier" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 51.539 51.361" width="18">
                                    <path d="M51.539,49.356L37.247,35.065c3.273-3.74,5.272-8.623,5.272-13.983c0-11.742-9.518-21.26-21.26-21.26 S0,9.339,0,21.082s9.518,21.26,21.26,21.26c5.361,0,10.244-1.999,13.983-5.272l14.292,14.292L51.539,49.356z M2.835,21.082 c0-10.176,8.249-18.425,18.425-18.425s18.425,8.249,18.425,18.425S31.436,39.507,21.26,39.507S2.835,31.258,2.835,21.082z"></path>
                                </svg>';
        
        // Get action URL with WooCommerce fallback
        $action_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

        // Generate unique class for preset
        $preset_class = $preset_id ? ' nivo-preset-' . $preset_id : '';
        
        // Generate inline styles for preset
        $inline_styles = '';
        if (!empty($preset_settings)) {
            $inline_styles = $this->generate_preset_styles($preset_id, $preset_settings);
        }
        
        ?>
        <?php if ($inline_styles): ?>
        <style><?php echo $inline_styles; ?></style>
        <?php endif; ?>
        <div class="nivo-ajax-search-container<?php echo esc_attr($preset_class); ?>" data-preset-id="<?php echo esc_attr($preset_id); ?>">
            <div class="nivo-search-form-wrapper">
                <form class="nivo-search-form" role="search" method="get" action="<?php echo esc_url($action_url) ?>">
                    <div class="nivo-search-wrapper nivo-search-box-style-<?php echo esc_attr($atts['search_bar_layout']); ?>" >

                        <?php echo $this->get_search_form_html($atts); ?>

                        <div class="nivo-search-search-icon-wrap">
                            <?php echo $search_icon_html;  ?>
                        </div>

                    </div>
                </form>
            </div>

            <div class="nivo-search-results"></div>
        </div>
<!-- <button type="submit" aria-label="Search" class="nivosearch-search-submit"></button> -->
        <?php
        $markup = ob_get_clean();
        return apply_filters('nivo_search_shortcode_html', $markup, $atts);
    }
    
    /**
     * Generate preset-specific styles
     *
     * @param int $preset_id Preset ID
     * @param array $settings Preset settings
     * @return string CSS styles
     */
    private function generate_preset_styles($preset_id, $settings) {
        $css = '';
        $selector = '.nivo-preset-' . $preset_id;
        
        // Search bar styles
        $css .= $selector . ' .nivo-search-form-wrapper { max-width: ' . $settings['bar_width'] . 'px; }';
        $css .= $selector . ' input.nivo-search-product-search {';
        $css .= 'height: ' . $settings['bar_height'] . 'px;';
        $css .= 'border: ' . $settings['border_width'] . 'px solid ' . $settings['border_color'] . ';';
        $css .= 'border-radius: ' . $settings['border_radius'] . 'px;';
        $css .= 'background-color: ' . $settings['bg_color'] . ';';
        $css .= 'color: ' . $settings['text_color'] . ';';
        $css .= '}';
        
        // Results styles
        $css .= $selector . ' .nivo-search-results {';
        $css .= 'border: ' . $settings['results_border_width'] . 'px solid ' . $settings['results_border_color'] . ';';
        $css .= 'border-radius: ' . $settings['results_border_radius'] . 'px;';
        $css .= 'background-color: ' . $settings['results_bg_color'] . ';';
        $css .= '}';
        
        return $css;
    }
}