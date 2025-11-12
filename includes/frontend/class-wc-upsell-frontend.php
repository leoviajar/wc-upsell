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
        
        // Hide quantity selector when kits are active
        add_filter( 'woocommerce_is_sold_individually', array( $this, 'hide_quantity_selector' ), 10, 2 );
    }

    /**
     * Display kit selector on product page
     */
    public function display_kit_selector() {
        global $product;
        
        if ( ! $product ) {
            return;
        }
        
        // Get the actual product (handling parent/variation)
        $product_id = $product->get_id();
        
        // For variable products, use the parent ID
        if ( $product->is_type( 'variation' ) ) {
            $product_id = $product->get_parent_id();
        }
        
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $kits = $product_kit->get_enabled_kits();
        
        if ( empty( $kits ) ) {
            return;
        }
        
        // Get regular price - handle different product types
        $regular_price = $product->get_regular_price();
        
        // If empty (like in variable products), try to get from variations or use price
        if ( empty( $regular_price ) || ! is_numeric( $regular_price ) ) {
            if ( $product->is_type( 'variable' ) ) {
                $regular_price = $product->get_variation_regular_price( 'min' );
            } else {
                $regular_price = $product->get_price();
            }
        }
        
        // Ensure it's numeric
        $regular_price = floatval( $regular_price );
        
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
     * Hide quantity selector when kits are active
     *
     * @param bool $sold_individually Whether product is sold individually
     * @param WC_Product $product Product object
     * @return bool
     */
    public function hide_quantity_selector( $sold_individually, $product ) {
        // Get the product ID (handle variations)
        $product_id = $product->get_id();
        if ( $product->is_type( 'variation' ) ) {
            $product_id = $product->get_parent_id();
        }
        
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $kits = $product_kit->get_enabled_kits();
        
        // If there are active kits, hide the quantity selector
        if ( ! empty( $kits ) ) {
            return true;
        }
        
        return $sold_individually;
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
        
        // Get regular price - handle different product types
        $regular_price = $product->get_regular_price();
        
        // If empty (like in variable products), try to get from variations or use price
        if ( empty( $regular_price ) || ! is_numeric( $regular_price ) ) {
            if ( $product->is_type( 'variable' ) ) {
                $regular_price = $product->get_variation_regular_price( 'min' );
            } else {
                $regular_price = $product->get_price();
            }
        }
        
        // Ensure it's numeric
        $regular_price = floatval( $regular_price );
        
        $pricing_engine = new WC_Upsell_Pricing_Engine();
        
        include WC_UPSELL_PLUGIN_DIR . 'includes/frontend/templates/kit-selector.php';
        
        return ob_get_clean();
    }
}
