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
        // Get preset ID from block attributes
        $preset_id = isset($attributes['presetId']) ? absint($attributes['presetId']) : 0;
        $preset_settings = [];
        
        if ($preset_id && get_post_type($preset_id) === 'nivo_search_preset') {
            $preset_settings = get_post_meta($preset_id, '_nivo_search_settings', true);
            if (!is_array($preset_settings)) {
                $preset_settings = [];
            }
        }
        
        // Use preset settings with defaults
        $placeholder = $preset_settings['placeholder'] ?? __('Search products...', 'nivo-ajax-search-for-woocommerce');
        $show_icon = $preset_settings['show_search_icon'] ?? 1;
        
        $icon_html = $show_icon ? '<svg class="nivo-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>' : '';
        
        // Get action URL with WooCommerce fallback
        $action_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
        
        // Generate preset class and styles
        $preset_class = $preset_id ? ' nivo-preset-' . $preset_id : '';
        $inline_styles = '';
        if (!empty($preset_settings)) {
            $inline_styles = $this->generate_preset_styles($preset_id, $preset_settings);
        }
        
        $output = '';
        if ($inline_styles) {
            $output .= '<style>' . $inline_styles . '</style>';
        }
        
        $output .= sprintf(
            '<div class="nivo-ajax-search-container nivo-search-block%s" data-preset-id="%d">
                <form class="nivo-search-form" role="search" method="get" action="%s">
                    <div class="nivo-search-wrapper">
                        %s
                        <input type="text" class="nivo-search-product-search" name="s" placeholder="%s" autocomplete="off">
                        <span class="nivo-search-clear-search" style="display:none;">&times;</span>
                    </div>
                </form>
                <div class="nivo-search-results"></div>
            </div>',
            esc_attr($preset_class),
            esc_attr($preset_id),
            esc_url($action_url),
            $icon_html,
            esc_attr($placeholder)
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
        $css = '';
        $selector = '.nivo-preset-' . $preset_id;
        
        if (isset($settings['bar_width'])) {
            $css .= $selector . ' { max-width: ' . $settings['bar_width'] . 'px; }';
        }
        
        if (isset($settings['border_width'], $settings['border_color'], $settings['border_radius'], $settings['bg_color'])) {
            $css .= $selector . ' input.nivo-search-product-search {';
            $css .= 'border: ' . $settings['border_width'] . 'px solid ' . $settings['border_color'] . ';';
            $css .= 'border-radius: ' . $settings['border_radius'] . 'px;';
            $css .= 'background-color: ' . $settings['bg_color'] . ';';
            if (isset($settings['text_color'])) {
                $css .= 'color: ' . $settings['text_color'] . ';';
            }
            $css .= '}';
        }
        
        if (isset($settings['results_border_width'], $settings['results_border_color'], $settings['results_border_radius'], $settings['results_bg_color'])) {
            $css .= $selector . ' .nivo-search-results {';
            $css .= 'border: ' . $settings['results_border_width'] . 'px solid ' . $settings['results_border_color'] . ';';
            $css .= 'border-radius: ' . $settings['results_border_radius'] . 'px;';
            $css .= 'background-color: ' . $settings['results_bg_color'] . ';';
            $css .= '}';
        }
        
        return $css;
    }
}