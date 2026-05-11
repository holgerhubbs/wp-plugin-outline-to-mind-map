<?php
/**
 * Plugin Name:       Outline to Mind Map
 * Plugin URI:        https://wordpress.org/plugins/outline-to-mind-map/
 * Description:       Transform any text outline into a visual, interactive mind map that simplifies complex ideas and invites users to explore them like a game—no coding required.
 * Version:           1.0.2
 * Requires at least: 5.8
 * Tested up to:      6.9
 * Requires PHP:      7.4
 * Author:            Holger Hubbs
 * Author URI:        https://www.12dollarwebsites.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       outline-to-mind-map
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'OTMM_VERSION', '1.0.2' );
define( 'OTMM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTMM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OTMM_CACHE_GROUP', 'otmm' );

require_once OTMM_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once OTMM_PLUGIN_DIR . 'includes/class-cache.php';
require_once OTMM_PLUGIN_DIR . 'admin/class-settings.php';

/**
 * Bootstrap the plugin.
 */
function otmm_init() {
    $cache    = new Outline_To_Mind_Map_Cache();
    $shortcode = new Outline_To_Mind_Map_Shortcode( $cache );
    $shortcode->register();

    if ( is_admin() ) {
        $settings = new Outline_To_Mind_Map_Settings();
        $settings->register();
    }
}
add_action( 'plugins_loaded', 'otmm_init' );

/**
 * Flush cache on plugin deactivation.
 */
function otmm_deactivate() {
    Outline_To_Mind_Map_Cache::flush_all();
}
register_deactivation_hook( __FILE__, 'otmm_deactivate' );

/**
 * Enqueue markmap assets on the frontend only when needed.
 *
 * We check whether the current post/page content contains our shortcode.
 * If it does, we enqueue. This covers both fresh renders and cached HTML,
 * because the check is against the raw post content (not the rendered output).
 * On pages without a mind map, no assets are loaded at all.
 */
function otmm_enqueue_if_needed() {
    if ( is_admin() || ! is_singular() ) {
        return;
    }
    global $post;
    if ( ! $post || ( strpos( $post->post_content, '[outline-to-mind-map' ) === false && strpos( $post->post_content, '[hh-mindmap' ) === false ) ) {
        return;
    }
    wp_enqueue_script(
        'markmap-autoloader',
        OTMM_PLUGIN_URL . 'assets/vendor/markmap-autoloader.js',
        [],
        OTMM_VERSION,
        true
    );
    wp_enqueue_style(
        'outline-to-mind-map',
        OTMM_PLUGIN_URL . 'assets/outline-to-mind-map.css',
        [],
        OTMM_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'otmm_enqueue_if_needed' );
