<?php
namespace NivoSearch;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Settings Page
 *
 * @package NivoSearch
 * @since 1.0.0
 */

/**
 * Admin Settings Class
 *
 * Handles plugin admin settings and configuration
 *
 * @since 1.0.0
 */
class Admin_Settings {
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_head', array( $this, 'remove_notices' ) );
    }
        
    /**
     * Add admin menu
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        // Add main menu
        add_menu_page(
            __('NivoSearch', 'nivo-ajax-search-for-woocommerce'),
            __('NivoSearch', 'nivo-ajax-search-for-woocommerce'),
            'manage_options',
            'nivo-search',
            array( $this, 'settings_page' ),
            'dashicons-search',
            56
        );
        
        // Add Settings submenu (rename the first submenu)
        add_submenu_page(
            'nivo-search',
            __('Settings', 'nivo-ajax-search-for-woocommerce'),
            __('Settings', 'nivo-ajax-search-for-woocommerce'),
            'manage_options',
            'nivo-search',
            array( $this, 'settings_page' )
        );
    }
    
    /**
     * Remove other plugin notices
     *
     * @since 1.1.0
     */
    public function remove_notices() {
        $screen = get_current_screen();
        
        if ( ! $screen ) {
            return;
        }
        
        // Remove notices from settings page and preset pages
        if ( $screen->id === 'toplevel_page_nivo-search' || 
             $screen->post_type === 'nivo_search_preset' ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
        }
    }
    
    /**
     * Enqueue admin styles
     *
     * @since 1.1.0
     */
    public function enqueue_admin_styles( $hook ) {
        if ( 'toplevel_page_nivo-search' !== $hook ) {
            return;
        }
        // Add inline styles
        wp_add_inline_style( 'wp-admin', '
            .nivo-settings-page {
                max-width: 1200px;
                margin: 40px auto;
                background: #fff;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .nivo-settings-page h1 {
                font-size: 32px;
                margin-bottom: 10px;
                color: #1d2327;
            }
            .nivo-settings-page .subtitle {
                font-size: 16px;
                color: #646970;
                margin-bottom: 40px;
            }
            .nivo-card {
                background: #f9f9f9;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 30px;
                margin-bottom: 30px;
            }
            .nivo-card h2 {
                margin-top: 0;
                font-size: 20px;
                color: #1d2327;
                border-bottom: 2px solid #2271b1;
                padding-bottom: 10px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .nivo-card h2 svg {
                width: 24px;
                height: 24px;
                fill: #2271b1;
            }
            .nivo-card ol {
                font-size: 15px;
                line-height: 1.8;
                color: #50575e;
            }
            .nivo-card ol li {
                margin-bottom: 12px;
            }
            .nivo-card code {
                background: #fff;
                padding: 2px 8px;
                border-radius: 4px;
                border: 1px solid #ddd;
                font-family: monospace;
                color: #d63638;
            }
            .nivo-feature-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            .nivo-feature-item {
                background: #fff;
                padding: 20px;
                border-radius: 6px;
                border: 1px solid #e0e0e0;
            }
            .nivo-feature-item h3 {
                margin-top: 0;
                font-size: 16px;
                color: #2271b1;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .nivo-feature-item h3 svg {
                width: 20px;
                height: 20px;
                fill: #2271b1;
            }
            .nivo-feature-item p {
                margin: 0;
                font-size: 14px;
                color: #646970;
            }
        ');
    }
    
    /**
     * Settings page HTML
     *
     * @since 1.0.0
     */
    public function settings_page() {
        $default_preset = get_option( 'nivo_search_default_preset_created') ?? '123';
        ?>
        <div class="nivo-settings-page">
            <h1><?php _e('NivoSearch', 'nivo-ajax-search-for-woocommerce'); ?></h1>
            <p class="subtitle"><?php _e('Advanced AJAX Product Search for WooCommerce', 'nivo-ajax-search-for-woocommerce'); ?></p>
            
            <div class="nivo-card">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    <?php _e('Quick Start Guide', 'nivo-ajax-search-for-woocommerce'); ?>
                </h2>
                <p><?php _e('Create unlimited search presets with custom settings:', 'nivo-ajax-search-for-woocommerce'); ?></p>
                <ol>
                    <li><?php _e('Go to <strong>NivoSearch → Search Presets</strong>', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><?php _e('Click <strong>"Add New Preset"</strong>', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><?php _e('Configure all settings (search scope, styling, display options)', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><?php _e('Click <strong>Publish</strong> to generate your shortcode', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><?php _e('Copy and use the shortcode anywhere: ', 'nivo-ajax-search-for-woocommerce'); ?><code>[nivo_search id="<?php echo esc_attr(  $default_preset ); ?>"]</code></li>
                </ol>
            </div>
            
            <div class="nivo-card">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    <?php _e('Key Features', 'nivo-ajax-search-for-woocommerce'); ?>
                </h2>
                <div class="nivo-feature-grid">
                    <div class="nivo-feature-item">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
                            <?php _e('High Performance', 'nivo-ajax-search-for-woocommerce'); ?>
                        </h3>
                        <p><?php _e('Optimized single query approach with ~200ms response time', 'nivo-ajax-search-for-woocommerce'); ?></p>
                    </div>
                    <div class="nivo-feature-item">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                            <?php _e('Unlimited Presets', 'nivo-ajax-search-for-woocommerce'); ?>
                        </h3>
                        <p><?php _e('Create multiple search bars with different designs and settings', 'nivo-ajax-search-for-woocommerce'); ?></p>
                    </div>
                    <div class="nivo-feature-item">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                            <?php _e('Smart Search', 'nivo-ajax-search-for-woocommerce'); ?>
                        </h3>
                        <p><?php _e('Search in title, SKU, description, categories, and tags', 'nivo-ajax-search-for-woocommerce'); ?></p>
                    </div>
                    <div class="nivo-feature-item">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg>
                            <?php _e('Fully Responsive', 'nivo-ajax-search-for-woocommerce'); ?>
                        </h3>
                        <p><?php _e('Perfect display on all devices with mobile-optimized design', 'nivo-ajax-search-for-woocommerce'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="nivo-card">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>
                    <?php _e('Documentation', 'nivo-ajax-search-for-woocommerce'); ?>
                </h2>
                <p><?php _e('Each preset can be configured with:', 'nivo-ajax-search-for-woocommerce'); ?></p>
                <ul style="font-size: 15px; line-height: 1.8; color: #50575e;">
                    <li><strong><?php _e('General Settings:', 'nivo-ajax-search-for-woocommerce'); ?></strong> <?php _e('Results limit, minimum characters, placeholder text', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><strong><?php _e('Search Scope:', 'nivo-ajax-search-for-woocommerce'); ?></strong> <?php _e('Title, SKU, description, short description, out of stock control', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><strong><?php _e('Others Content:', 'nivo-ajax-search-for-woocommerce'); ?></strong> <?php _e('Show categories and tags in results', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><strong><?php _e('Display Options:', 'nivo-ajax-search-for-woocommerce'); ?></strong> <?php _e('Product images, prices, SKU, descriptions', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><strong><?php _e('Search Bar Styling:', 'nivo-ajax-search-for-woocommerce'); ?></strong> <?php _e('Width, height, colors, borders, radius', 'nivo-ajax-search-for-woocommerce'); ?></li>
                    <li><strong><?php _e('Results Styling:', 'nivo-ajax-search-for-woocommerce'); ?></strong> <?php _e('Borders, background, padding, layout', 'nivo-ajax-search-for-woocommerce'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}