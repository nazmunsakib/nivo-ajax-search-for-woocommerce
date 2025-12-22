/**
 * Admin JavaScript
 *
 * @package NivoSearch
 * @since 1.1.0
 */

// No admin JavaScript needed - settings page is static HTML
jQuery(document).ready(function ($) {
    $('.nivo-color-picker').wpColorPicker({
        defaultColor: false, // No default
        change: function (event, ui) { },
        clear: function () { }, // Fires when cleared
        hide: true,
        palettes: true
    });
});