<?php
/**
 * Product Kit Class
 *
 * Handles individual product kit data and operations
 *
 * @package WC_Upsell
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Upsell_Product_Kit {

    /**
     * Kit ID
     *
     * @var int
     */
    private $id;

    /**
     * Product ID
     *
     * @var int
     */
    private $product_id;

    /**
     * Kit data
     *
     * @var array
     */
    private $data = array();

    /**
     * Constructor
     *
     * @param int $product_id Product ID
     */
    public function __construct( $product_id = 0 ) {
        if ( $product_id ) {
            $this->product_id = $product_id;
            $this->load_data();
        }
    }

    /**
     * Load kit data from database
     */
    private function load_data() {
        $kits = get_post_meta( $this->product_id, '_wc_upsell_kits', true );
        $this->data = is_array( $kits ) ? $kits : array();
    }

    /**
     * Get all kits for the product
     *
     * @return array
     */
    public function get_kits() {
        return $this->data;
    }

    /**
     * Get specific kit by quantity
     *
     * @param int $quantity
     * @return array|false
     */
    public function get_kit_by_quantity( $quantity ) {
        foreach ( $this->data as $kit ) {
            if ( isset( $kit['quantity'] ) && (int) $kit['quantity'] === (int) $quantity ) {
                return $kit;
            }
        }
        return false;
    }

    /**
     * Add or update a kit
     *
     * @param array $kit_data Kit configuration
     * @return bool
     */
    public function save_kit( $kit_data ) {
        $quantity = isset( $kit_data['quantity'] ) ? absint( $kit_data['quantity'] ) : 0;
        
        if ( ! $quantity ) {
            return false;
        }

        // Sanitize kit data
        $sanitized_kit = array(
            'quantity' => $quantity,
            'price' => floatval( $kit_data['price'] ),
            'badge_text' => isset( $kit_data['badge_text'] ) ? sanitize_text_field( $kit_data['badge_text'] ) : '',
            'badge_color' => isset( $kit_data['badge_color'] ) ? sanitize_hex_color( $kit_data['badge_color'] ) : '#000000',
            'enabled' => isset( $kit_data['enabled'] ) ? (bool) $kit_data['enabled'] : true,
        );

        // Apply filter
        $sanitized_kit = apply_filters( 'wc_upsell_before_save_kit', $sanitized_kit, $this->product_id );

        // Update or add kit
        $found = false;
        foreach ( $this->data as $key => $kit ) {
            if ( isset( $kit['quantity'] ) && (int) $kit['quantity'] === $quantity ) {
                $this->data[ $key ] = $sanitized_kit;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            $this->data[] = $sanitized_kit;
        }

        // Sort by quantity
        usort( $this->data, function( $a, $b ) {
            return $a['quantity'] - $b['quantity'];
        });

        // Save to database
        $result = update_post_meta( $this->product_id, '_wc_upsell_kits', $this->data );

        // Action hook
        do_action( 'wc_upsell_after_save_kit', $sanitized_kit, $this->product_id );

        return $result !== false;
    }

    /**
     * Delete a kit by quantity
     *
     * @param int $quantity
     * @return bool
     */
    public function delete_kit( $quantity ) {
        foreach ( $this->data as $key => $kit ) {
            if ( isset( $kit['quantity'] ) && (int) $kit['quantity'] === (int) $quantity ) {
                unset( $this->data[ $key ] );
                $this->data = array_values( $this->data ); // Reindex array
                return update_post_meta( $this->product_id, '_wc_upsell_kits', $this->data ) !== false;
            }
        }
        return false;
    }

    /**
     * Check if product has any kits configured
     *
     * @return bool
     */
    public function has_kits() {
        return ! empty( $this->data );
    }

    /**
     * Get enabled kits only
     *
     * @return array
     */
    public function get_enabled_kits() {
        return array_filter( $this->data, function( $kit ) {
            return isset( $kit['enabled'] ) && $kit['enabled'];
        });
    }

    /**
     * Get product ID
     *
     * @return int
     */
    public function get_product_id() {
        return $this->product_id;
    }

    /**
     * Calculate savings for a kit
     *
     * @param array $kit
     * @return float
     */
    public function calculate_savings( $kit ) {
        $product = wc_get_product( $this->product_id );
        
        if ( ! $product ) {
            return 0;
        }

        $regular_price = $product->get_regular_price();
        $quantity = isset( $kit['quantity'] ) ? $kit['quantity'] : 1;
        $kit_price = isset( $kit['price'] ) ? $kit['price'] : 0;

        $normal_total = $regular_price * $quantity;
        $savings = $normal_total - $kit_price;

        return max( 0, $savings );
    }

    /**
     * Calculate percentage discount
     *
     * @param array $kit
     * @return float
     */
    public function calculate_discount_percentage( $kit ) {
        $product = wc_get_product( $this->product_id );
        
        if ( ! $product ) {
            return 0;
        }

        $regular_price = $product->get_regular_price();
        $quantity = isset( $kit['quantity'] ) ? $kit['quantity'] : 1;
        $kit_price = isset( $kit['price'] ) ? $kit['price'] : 0;

        $normal_total = $regular_price * $quantity;
        
        if ( $normal_total <= 0 ) {
            return 0;
        }

        $savings = $normal_total - $kit_price;
        $percentage = ( $savings / $normal_total ) * 100;

        return round( max( 0, $percentage ), 2 );
    }
}
