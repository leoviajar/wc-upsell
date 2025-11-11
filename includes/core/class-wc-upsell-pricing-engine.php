<?php
/**
 * Pricing Engine Class
 *
 * Handles all pricing calculations for upsell kits
 *
 * @package WC_Upsell
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Upsell_Pricing_Engine {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WooCommerce pricing
        add_filter( 'woocommerce_product_get_price', array( $this, 'modify_product_price' ), 10, 2 );
        add_filter( 'woocommerce_product_variation_get_price', array( $this, 'modify_product_price' ), 10, 2 );
    }

    /**
     * Calculate kit price with discounts
     *
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @return float|false Kit price or false if no kit found
     */
    public function calculate_kit_price( $product_id, $quantity ) {
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $kit = $product_kit->get_kit_by_quantity( $quantity );

        if ( ! $kit || ! isset( $kit['price'] ) ) {
            return false;
        }

        $price = floatval( $kit['price'] );

        // Apply filter for custom pricing logic
        $price = apply_filters( 'wc_upsell_calculated_kit_price', $price, $product_id, $quantity );

        return $price;
    }

    /**
     * Get price per unit in a kit
     *
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @return float Unit price
     */
    public function get_unit_price( $product_id, $quantity ) {
        $kit_price = $this->calculate_kit_price( $product_id, $quantity );

        if ( $kit_price === false || $quantity <= 0 ) {
            $product = wc_get_product( $product_id );
            return $product ? floatval( $product->get_price() ) : 0;
        }

        return $kit_price / $quantity;
    }

    /**
     * Format price for display
     *
     * @param float $price Price to format
     * @return string Formatted price HTML
     */
    public function format_price( $price ) {
        return wc_price( $price );
    }

    /**
     * Format unit price for display
     *
     * @param float $unit_price Unit price
     * @param int $quantity Quantity
     * @return string Formatted unit price HTML
     */
    public function format_unit_price( $unit_price, $quantity ) {
        $formatted_price = wc_price( $unit_price );
        /* translators: %s: unit price */
        return sprintf( __( '%s / Uni.', 'wc-upsell' ), $formatted_price );
    }

    /**
     * Calculate savings amount
     *
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @return float Savings amount
     */
    public function calculate_savings( $product_id, $quantity ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return 0;
        }

        $regular_price = floatval( $product->get_regular_price() );
        $kit_price = $this->calculate_kit_price( $product_id, $quantity );

        if ( $kit_price === false ) {
            return 0;
        }

        $normal_total = $regular_price * $quantity;
        $savings = $normal_total - $kit_price;

        return max( 0, $savings );
    }

    /**
     * Calculate discount percentage
     *
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @return float Discount percentage
     */
    public function calculate_discount_percentage( $product_id, $quantity ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return 0;
        }

        $regular_price = floatval( $product->get_regular_price() );
        $kit_price = $this->calculate_kit_price( $product_id, $quantity );

        if ( $kit_price === false || $regular_price <= 0 ) {
            return 0;
        }

        $normal_total = $regular_price * $quantity;
        
        if ( $normal_total <= 0 ) {
            return 0;
        }

        $savings = $normal_total - $kit_price;
        $percentage = ( $savings / $normal_total ) * 100;

        return round( max( 0, $percentage ), 2 );
    }

    /**
     * Get all pricing data for a kit
     *
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @return array Pricing data
     */
    public function get_kit_pricing_data( $product_id, $quantity ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return array();
        }

        $kit_price = $this->calculate_kit_price( $product_id, $quantity );
        
        if ( $kit_price === false ) {
            return array();
        }

        $regular_price = floatval( $product->get_regular_price() );
        $normal_total = $regular_price * $quantity;
        $unit_price = $kit_price / $quantity;
        $savings = $this->calculate_savings( $product_id, $quantity );
        $discount_percentage = $this->calculate_discount_percentage( $product_id, $quantity );

        return array(
            'kit_price' => $kit_price,
            'kit_price_formatted' => $this->format_price( $kit_price ),
            'unit_price' => $unit_price,
            'unit_price_formatted' => $this->format_unit_price( $unit_price, $quantity ),
            'regular_total' => $normal_total,
            'regular_total_formatted' => $this->format_price( $normal_total ),
            'savings' => $savings,
            'savings_formatted' => $this->format_price( $savings ),
            'discount_percentage' => $discount_percentage,
            'discount_percentage_formatted' => $discount_percentage . '%',
        );
    }

    /**
     * Modify product price in cart
     *
     * @param float $price Current price
     * @param WC_Product $product Product object
     * @return float Modified price
     */
    public function modify_product_price( $price, $product ) {
        // This will be used when item is in cart with kit quantity
        // The actual implementation will be in cart handler
        return $price;
    }

    /**
     * Validate kit price
     *
     * @param float $price Kit price
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_kit_price( $price, $product_id, $quantity ) {
        if ( $price < 0 ) {
            return new WP_Error( 'invalid_price', __( 'O preço do kit não pode ser negativo.', 'wc-upsell' ) );
        }

        if ( $quantity <= 0 ) {
            return new WP_Error( 'invalid_quantity', __( 'A quantidade do kit deve ser maior que zero.', 'wc-upsell' ) );
        }

        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return new WP_Error( 'invalid_product', __( 'Produto inválido.', 'wc-upsell' ) );
        }

        return true;
    }
}
