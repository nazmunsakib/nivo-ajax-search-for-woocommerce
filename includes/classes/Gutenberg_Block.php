<?php
/**
 * Gutenberg Block Handler
 *
 * @package NivoSearch
 * @since 1.0.0
 */

namespace NivoSearch;

defined('ABSPATH') || exit;

/**
 * Gutenberg Block Class
 *
 * Handles Gutenberg block registration and functionality
 *
 * @since 1.0.0
 */
class Gutenberg_Block {
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_assets']);
    }
    
    /**
     * Register Gutenberg block
     *
     * @since 1.0.0
     */
    public function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        register_block_type('nivo-search/ajax-search', [
            'attributes' => [
                'presetId' => [
                    'type' => 'number',
                    'default' => 0
                ]
            ],
            'render_callback' => [$this, 'render_block']
        ]);
    }
    
    /**
     * Enqueue block editor assets
     *
     * @since 1.0.0
     */
    public function enqueue_block_assets() {
        wp_enqueue_script(
            'nivo-search-block-editor',
            NIVO_SEARCH_PLUGIN_URL . 'assets/js/block-editor.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'],
            NIVO_SEARCH_VERSION,
            true
        );
    }
    
    /**
     * Render block on frontend
     *
     * @since 1.0.0
     * @param array $attributes Block attributes
     * @return string Block HTML
     */
    public function render_block($attributes = []) {
        // Get default preset if no preset specified
        $default_preset = Helper::get_default_preset_id();
        $preset_id = ( isset($attributes['presetId']) && $attributes['presetId'] !== 0 ) ? absint($attributes['presetId']) : $default_preset;

        // Get preset settings using Helper
        $preset_settings = Helper::get_preset_settings($preset_id);
        $preset_style_settings = Helper::get_preset_style_settings($preset_id);
        
        // Use preset settings with defaults
        $placeholder = $preset_settings['placeholder'] ?? __('Search products...', 'nivo-ajax-search-for-woocommerce');
        $search_bar_layout = $preset_settings['search_bar_layout'] ?? 1;
        
        $search_icon_html = '<svg class="nivosearch-ico-magnifier" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 51.539 51.361" width="18">
                                <path d="M51.539,49.356L37.247,35.065c3.273-3.74,5.272-8.623,5.272-13.983c0-11.742-9.518-21.26-21.26-21.26 S0,9.339,0,21.082s9.518,21.26,21.26,21.26c5.361,0,10.244-1.999,13.983-5.272l14.292,14.292L51.539,49.356z M2.835,21.082 c0-10.176,8.249-18.425,18.425-18.425s18.425,8.249,18.425,18.425S31.436,39.507,21.26,39.507S2.835,31.258,2.835,21.082z"></path>
                            </svg>';
        
        // Get action URL with WooCommerce fallback
        $action_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
        
        // Generate preset class
        $preset_class = $preset_id ? ' nivo-preset-' . $preset_id : '';
        
        // Generate inline styles if needed
        $inline_styles = '';
        if (!empty($preset_style_settings)) {
            $inline_styles = $this->generate_preset_styles($preset_id, $preset_style_settings);
        }
        
        $settings_compress = !empty($preset_settings) ? json_encode($preset_settings) : '{}';
        
        $output = '';
        if ($inline_styles) {
            $output .= '<style>' . $inline_styles . '</style>';
        }
        
        $output .= sprintf(
            '<div class="nivo-ajax-search-container%s" data-preset-id="%d" data-preset-settings="%s">
                <div class="nivo-search-form-wrapper">
                    <form class="nivo-search-form" role="search" method="get" action="%s">
                        <div class="nivo-search-wrapper nivo-search-box-style-%d">
                            <input type="search" class="nivo-search-product-search" name="s" placeholder="%s" autocomplete="off">
                            <div class="nivo-search-loader-icons">
                                <svg class="nivo-search-close-icon" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 24 24" width="18">
                                    <path d="M18.3 5.71c-.39-.39-1.02-.39-1.41 0L12 10.59 7.11 5.7c-.39-.39-1.02-.39-1.41 0-.39.39-.39 1.02 0 1.41L10.59 12 5.7 16.89c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0L12 13.41l4.89 4.89c.39.39 1.02.39 1.41 0 .39-.39.39-1.02 0-1.41L13.41 12l4.89-4.89c.38-.38.38-1.02 0-1.4z"></path>
                                </svg>
                            </div>
                            <div class="nivo-search-search-icon-wrap">
                                %s
                            </div>
                        </div>
                    </form>
                </div>
                <div class="nivo-search-results"></div>
            </div>',
            esc_attr($preset_class),
            esc_attr($preset_id),
            esc_attr($settings_compress),
            esc_url($action_url),
            esc_attr($search_bar_layout),
            esc_attr($placeholder),
            $search_icon_html
        );
        
        return $output;
    }
    
    /**
     * Generate preset-specific styles
     *
     * @param int $preset_id Preset ID
     * @param array $settings Preset settings
     * @return string CSS styles
     */
    private function generate_preset_styles($preset_id, $settings) {
        return Helper::generate_preset_css($preset_id, $settings);
    }
}