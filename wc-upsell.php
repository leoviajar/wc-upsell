<?php
/**
 * Plugin Name: WC Upsell
 * Plugin URI: https://embreve.com
 * Description: Plugin profissional de upsell com kits e descontos progressivos para WooCommerce
 * Version: 1.0.1
 * Author: Leonardo
 * Author URI: https://embreve.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-upsell
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define( 'WC_UPSELL_VERSION', '1.0.0' );
define( 'WC_UPSELL_PLUGIN_FILE', __FILE__ );
define( 'WC_UPSELL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_UPSELL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_UPSELL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
if ( ! function_exists( 'wc_upsell_is_woocommerce_active' ) ) {
    function wc_upsell_is_woocommerce_active() {
        return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }
}

/**
 * Initialize the plugin
 */
function wc_upsell_init() {
    // Check if WooCommerce is active
    if ( ! wc_upsell_is_woocommerce_active() ) {
        add_action( 'admin_notices', 'wc_upsell_woocommerce_missing_notice' );
        return;
    }

    // Load plugin text domain
    load_plugin_textdomain( 'wc-upsell', false, dirname( WC_UPSELL_PLUGIN_BASENAME ) . '/languages' );

    // Include required files
    require_once WC_UPSELL_PLUGIN_DIR . 'includes/class-wc-upsell.php';

    // Initialize the main plugin class
    WC_Upsell::instance();
}
add_action( 'plugins_loaded', 'wc_upsell_init' );

/**
 * Setup plugin updates from GitHub
 */
require WC_UPSELL_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/leoviajar/wc-upsell/',
    __FILE__,
    'wc-upsell'
);

// Set the branch that contains the stable release
$myUpdateChecker->setBranch('main');

// Optional: If your GitHub repo is private, specify the access token
// $myUpdateChecker->setAuthentication('your-token-here');

/**
 * Display notice if WooCommerce is not active
 */
function wc_upsell_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e( 'WC Upsell requer o WooCommerce para funcionar. Por favor, instale e ative o WooCommerce.', 'wc-upsell' ); ?></p>
    </div>
    <?php
}

/**
 * Declare HPOS compatibility
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * Activation hook
 */
function wc_upsell_activate() {
    // Check WooCommerce
    if ( ! wc_upsell_is_woocommerce_active() ) {
        deactivate_plugins( WC_UPSELL_PLUGIN_BASENAME );
        wp_die( __( 'Por favor, instale e ative o WooCommerce antes de ativar o WC Upsell.', 'wc-upsell' ), '', array( 'back_link' => true ) );
    }

    // Set default options
    if ( ! get_option( 'wc_upsell_version' ) ) {
        update_option( 'wc_upsell_version', WC_UPSELL_VERSION );
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wc_upsell_activate' );

/**
 * Deactivation hook
 */
function wc_upsell_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wc_upsell_deactivate' );
