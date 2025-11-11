<?php
/**
 * Frontend Class
 *
 * Handles frontend display and functionality
 *
 * @package WC_Upsell
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Upsell_Frontend {

    /**
     * Constructor
     */
    public function __construct() {
        // Display kit selector on product page
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_kit_selector' ), 5 );
        
        // Modify add to cart button
        add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'modify_add_to_cart_text' ), 10, 2 );
    }

    /**
     * Display kit selector on product page
     */
    public function display_kit_selector() {
        global $product;
        
        if ( ! $product ) {
            return;
        }
        
        $product_kit = new WC_Upsell_Product_Kit( $product->get_id() );
        $kits = $product_kit->get_enabled_kits();
        
        if ( empty( $kits ) ) {
            return;
        }
        
        $regular_price = $product->get_regular_price();
        $pricing_engine = new WC_Upsell_Pricing_Engine();
        
        include WC_UPSELL_PLUGIN_DIR . 'includes/frontend/templates/kit-selector.php';
    }

    /**
     * Modify add to cart button text
     *
     * @param string $text Button text
     * @param WC_Product $product Product object
     * @return string Modified text
     */
    public function modify_add_to_cart_text( $text, $product ) {
        $product_kit = new WC_Upsell_Product_Kit( $product->get_id() );
        
        if ( $product_kit->has_kits() ) {
            return __( 'Adicionar ao Carrinho', 'wc-upsell' );
        }
        
        return $text;
    }

    /**
     * Get kit selector HTML
     *
     * @param int $product_id Product ID
     * @return string HTML
     */
    public function get_kit_selector_html( $product_id ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return '';
        }
        
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $kits = $product_kit->get_enabled_kits();
        
        if ( empty( $kits ) ) {
            return '';
        }
        
        ob_start();
        
        $regular_price = $product->get_regular_price();
        $pricing_engine = new WC_Upsell_Pricing_Engine();
        
        include WC_UPSELL_PLUGIN_DIR . 'includes/frontend/templates/kit-selector.php';
        
        return ob_get_clean();
    }
}
