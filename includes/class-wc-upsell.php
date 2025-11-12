<?php
/**
 * Main plugin class
 *
 * @package WC_Upsell
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Upsell {

    /**
     * Single instance of the class
     *
     * @var WC_Upsell
     */
    private static $instance = null;

    /**
     * Admin instance
     *
     * @var WC_Upsell_Admin
     */
    public $admin;

    /**
     * Frontend instance
     *
     * @var WC_Upsell_Frontend
     */
    public $frontend;

    /**
     * Pricing engine instance
     *
     * @var WC_Upsell_Pricing_Engine
     */
    public $pricing;

    /**
     * Cart handler instance
     *
     * @var WC_Upsell_Cart_Handler
     */
    public $cart;

    /**
     * Get instance
     *
     * @return WC_Upsell
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core
        require_once WC_UPSELL_PLUGIN_DIR . 'includes/core/class-wc-upsell-pricing-engine.php';
        require_once WC_UPSELL_PLUGIN_DIR . 'includes/core/class-wc-upsell-cart-handler.php';
        require_once WC_UPSELL_PLUGIN_DIR . 'includes/core/class-wc-upsell-product-kit.php';

        // Admin
        if ( is_admin() ) {
            require_once WC_UPSELL_PLUGIN_DIR . 'includes/admin/class-wc-upsell-admin.php';
            require_once WC_UPSELL_PLUGIN_DIR . 'includes/admin/class-wc-upsell-settings.php';
            $this->admin = new WC_Upsell_Admin();
            $this->settings = new WC_Upsell_Settings();
        }

        // Frontend
        require_once WC_UPSELL_PLUGIN_DIR . 'includes/frontend/class-wc-upsell-frontend.php';
        $this->frontend = new WC_Upsell_Frontend();

        // Initialize instances
        $this->pricing = new WC_Upsell_Pricing_Engine();
        $this->cart = new WC_Upsell_Cart_Handler();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Add settings link
        add_filter( 'plugin_action_links_' . WC_UPSELL_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if ( ! is_product() ) {
            return;
        }

        wp_enqueue_style( 
            'wc-upsell-frontend', 
            WC_UPSELL_PLUGIN_URL . 'assets/css/frontend.css', 
            array(), 
            WC_UPSELL_VERSION 
        );
        
        // Add custom colors
        $custom_css = $this->get_custom_colors_css();
        if ( $custom_css ) {
            wp_add_inline_style( 'wc-upsell-frontend', $custom_css );
        }

        wp_enqueue_script( 
            'wc-upsell-frontend', 
            WC_UPSELL_PLUGIN_URL . 'assets/js/frontend.js', 
            array( 'jquery' ), 
            WC_UPSELL_VERSION, 
            true 
        );

        wp_localize_script( 'wc-upsell-frontend', 'wcUpsellParams', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wc-upsell-nonce' ),
            'i18n' => array(
                'select_quantity' => __( 'Selecione a quantidade', 'wc-upsell' ),
                'add_to_cart' => __( 'Adicionar ao carrinho', 'wc-upsell' ),
            ),
        ) );
    }
    
    /**
     * Get custom colors CSS
     */
    private function get_custom_colors_css() {
        $border_color = get_option( 'wc_upsell_border_color' );
        $border_color_selected = get_option( 'wc_upsell_border_color_selected' );
        $bg_color = get_option( 'wc_upsell_bg_color' );
        $bg_color_selected = get_option( 'wc_upsell_bg_color_selected' );
        $text_color = get_option( 'wc_upsell_text_color' );
        $badge_bg_color = get_option( 'wc_upsell_badge_bg_color' );
        $badge_text_color = get_option( 'wc_upsell_badge_text_color' );
        $unit_price_bg_color = get_option( 'wc_upsell_unit_price_bg_color' );
        $unit_price_text_color = get_option( 'wc_upsell_unit_price_text_color' );
        
        $css = '';
        
        if ( $border_color ) {
            $css .= ".wc-upsell-kit-option { border-color: {$border_color} !important; }\n";
        }
        
        if ( $border_color_selected ) {
            $css .= ".wc-upsell-kit-option.selected { border-color: {$border_color_selected} !important; }\n";
            $css .= ".wc-upsell-kit-option:hover { border-color: {$border_color_selected} !important; }\n";
            $css .= ".wc-upsell-radio input[type='radio']:hover { border-color: {$border_color_selected} !important; }\n";
            $css .= ".wc-upsell-radio input[type='radio']:checked { border-color: {$border_color_selected} !important; }\n";
            $css .= ".wc-upsell-radio input[type='radio']:checked::after { background: {$border_color_selected} !important; }\n";
        }
        
        if ( $bg_color ) {
            $css .= ".wc-upsell-kit-option { background-color: {$bg_color} !important; }\n";
        }
        
        if ( $bg_color_selected ) {
            $css .= ".wc-upsell-kit-option.selected { background-color: {$bg_color_selected} !important; }\n";
        }
        
        if ( $text_color ) {
            $css .= ".wc-upsell-kit-option { color: {$text_color} !important; }\n";
            $css .= ".wc-upsell-quantity { color: {$text_color} !important; }\n";
        }
        
        if ( $badge_bg_color ) {
            $css .= ".wc-upsell-badge { background-color: {$badge_bg_color} !important; }\n";
        }
        
        if ( $badge_text_color ) {
            $css .= ".wc-upsell-badge { color: {$badge_text_color} !important; }\n";
        }
        
        if ( $unit_price_bg_color ) {
            $css .= ".wc-upsell-unit-price-badge { background-color: {$unit_price_bg_color} !important; }\n";
        }
        
        if ( $unit_price_text_color ) {
            $css .= ".wc-upsell-unit-price-badge,\n";
            $css .= ".wc-upsell-unit-price-badge .woocommerce-Price-amount,\n";
            $css .= ".wc-upsell-unit-price-badge bdi { color: {$unit_price_text_color} !important; }\n";
        }
        
        return $css;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        // Load on product edit page and our settings page
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) && strpos( $hook, 'wc-upsell' ) === false ) {
            return;
        }

        // Check if we're editing a product
        global $post_type;
        if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && $post_type !== 'product' ) {
            return;
        }

        wp_enqueue_style( 
            'wc-upsell-admin', 
            WC_UPSELL_PLUGIN_URL . 'assets/css/admin.css', 
            array(), 
            WC_UPSELL_VERSION 
        );

        wp_enqueue_script( 
            'wc-upsell-admin', 
            WC_UPSELL_PLUGIN_URL . 'assets/js/admin.js', 
            array( 'jquery', 'wp-color-picker' ), 
            WC_UPSELL_VERSION, 
            true 
        );

        wp_localize_script( 'wc-upsell-admin', 'wcUpsellAdminParams', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wc-upsell-admin-nonce' ),
        ) );
    }

    /**
     * Add plugin action links
     */
    public function plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=wc-upsell-settings' ) . '">' . __( 'Configurações', 'wc-upsell' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version() {
        return WC_UPSELL_VERSION;
    }
}
