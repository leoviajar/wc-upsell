<?php
/**
 * Cart Handler Class
 *
 * Handles adding upsell kits to cart and applying custom pricing
 *
 * @package WC_Upsell
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Upsell_Cart_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Add kit data to cart item
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_kit_data_to_cart' ), 10, 3 );
        add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_kit_data_from_session' ), 10, 2 );
        
        // Modify cart item price
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'update_cart_item_prices' ), 10, 1 );
        
        // Display kit info in cart
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_kit_info_in_cart' ), 10, 2 );
        
        // AJAX handler for adding kit to cart
        add_action( 'wp_ajax_wc_upsell_add_kit', array( $this, 'ajax_add_kit_to_cart' ) );
        add_action( 'wp_ajax_nopriv_wc_upsell_add_kit', array( $this, 'ajax_add_kit_to_cart' ) );
    }
    
    /**
     * AJAX handler for adding kit with multiple variations
     */
    public function ajax_add_kit_to_cart() {
        check_ajax_referer( 'wc-upsell-nonce', 'security' );
        
        if ( ! isset( $_POST['kit_data'] ) ) {
            wp_send_json_error( __( 'Dados inválidos.', 'wc-upsell' ) );
        }
        
        $kit_data = json_decode( wp_unslash( $_POST['kit_data'] ), true );
        
        if ( ! $kit_data || ! isset( $kit_data['product_id'] ) || ! isset( $kit_data['quantity'] ) || ! isset( $kit_data['variations'] ) ) {
            wp_send_json_error( __( 'Dados do kit inválidos.', 'wc-upsell' ) );
        }
        
        $product_id = absint( $kit_data['product_id'] );
        $quantity = absint( $kit_data['quantity'] );
        $variations = $kit_data['variations'];
        
        // Get kit pricing
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $kit = $product_kit->get_kit_by_quantity( $quantity );
        
        if ( ! $kit ) {
            wp_send_json_error( __( 'Kit não encontrado.', 'wc-upsell' ) );
        }
        
        $unit_price = floatval( $kit['price'] ) / $quantity;
        
        // Add each variation to cart
        $added_items = array();
        foreach ( $variations as $variation_data ) {
            if ( ! isset( $variation_data['variation_id'] ) || ! isset( $variation_data['attributes'] ) ) {
                continue;
            }
            
            $variation_id = absint( $variation_data['variation_id'] );
            $attributes = $variation_data['attributes'];
            
            // Add to cart with kit data
            $cart_item_key = WC()->cart->add_to_cart(
                $product_id,
                1,
                $variation_id,
                $attributes,
                array(
                    'wc_upsell_kit' => array(
                        'quantity' => $quantity,
                        'kit_price' => $kit['price'],
                        'badge_text' => isset( $kit['badge_text'] ) ? $kit['badge_text'] : '',
                    ),
                    'wc_upsell_unit_price' => $unit_price,
                    'wc_upsell_unique_key' => md5( microtime() . rand() ),
                )
            );
            
            if ( $cart_item_key ) {
                $added_items[] = $cart_item_key;
            }
        }
        
        if ( ! empty( $added_items ) ) {
            WC()->cart->calculate_totals();
            
            wp_send_json_success( array(
                'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
                'cart_hash' => WC()->cart->get_cart_hash(),
                'cart_url' => wc_get_cart_url(),
            ) );
        } else {
            wp_send_json_error( __( 'Não foi possível adicionar o kit ao carrinho.', 'wc-upsell' ) );
        }
    }

    /**
     * Add kit data to cart item
     *
     * @param array $cart_item_data Cart item data
     * @param int $product_id Product ID
     * @param int $variation_id Variation ID
     * @return array Modified cart item data
     */
    public function add_kit_data_to_cart( $cart_item_data, $product_id, $variation_id ) {
        // Check if this is a kit purchase
        if ( isset( $_POST['wc_upsell_kit'] ) && isset( $_POST['wc_upsell_quantity'] ) ) {
            $quantity = absint( $_POST['wc_upsell_quantity'] );
            
            // Get kit data
            $product_kit = new WC_Upsell_Product_Kit( $product_id );
            $kit = $product_kit->get_kit_by_quantity( $quantity );
            
            if ( $kit ) {
                $cart_item_data['wc_upsell_kit'] = array(
                    'quantity' => $quantity,
                    'kit_price' => $kit['price'],
                    'badge_text' => isset( $kit['badge_text'] ) ? $kit['badge_text'] : '',
                );
                
                // Add unique key to prevent merging with non-kit items
                $cart_item_data['wc_upsell_unique_key'] = md5( microtime() . rand() );
            }
        }
        
        return $cart_item_data;
    }

    /**
     * Get kit data from session
     *
     * @param array $cart_item Cart item
     * @param array $values Values from session
     * @return array Modified cart item
     */
    public function get_kit_data_from_session( $cart_item, $values ) {
        if ( isset( $values['wc_upsell_kit'] ) ) {
            $cart_item['wc_upsell_kit'] = $values['wc_upsell_kit'];
        }
        
        if ( isset( $values['wc_upsell_unique_key'] ) ) {
            $cart_item['wc_upsell_unique_key'] = $values['wc_upsell_unique_key'];
        }
        
        return $cart_item;
    }

    /**
     * Update cart item prices for kits
     *
     * @param WC_Cart $cart Cart object
     */
    public function update_cart_item_prices( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }
        
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            // Check for unit price (when variation added via AJAX)
            if ( isset( $cart_item['wc_upsell_unit_price'] ) ) {
                $unit_price = floatval( $cart_item['wc_upsell_unit_price'] );
                $cart_item['data']->set_price( $unit_price );
            }
            // Or check for kit data (simple products)
            elseif ( isset( $cart_item['wc_upsell_kit'] ) ) {
                $kit_data = $cart_item['wc_upsell_kit'];
                $kit_quantity = $kit_data['quantity'];
                $kit_price = $kit_data['kit_price'];
                
                // Calculate unit price
                $unit_price = $kit_price / $kit_quantity;
                
                // Set the new price
                $cart_item['data']->set_price( $unit_price );
            }
        }
    }

    /**
     * Display kit information in cart
     *
     * @param array $item_data Item data
     * @param array $cart_item Cart item
     * @return array Modified item data
     */
    public function display_kit_info_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['wc_upsell_kit'] ) ) {
            $kit_data = $cart_item['wc_upsell_kit'];
            
            $item_data[] = array(
                'key' => __( 'Kit', 'wc-upsell' ),
                'value' => sprintf( 
                    /* translators: %d: kit quantity */
                    __( '%d Unidades', 'wc-upsell' ), 
                    $kit_data['quantity'] 
                ),
                'display' => '',
            );
            
            if ( ! empty( $kit_data['badge_text'] ) ) {
                $item_data[] = array(
                    'key' => __( 'Oferta', 'wc-upsell' ),
                    'value' => esc_html( $kit_data['badge_text'] ),
                    'display' => '',
                );
            }
        }
        
        return $item_data;
    }

    /**
     * AJAX handler for adding kit to cart
     */
    public function ajax_add_to_cart() {
        check_ajax_referer( 'wc-upsell-nonce', 'nonce' );
        
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
        $variations = isset( $_POST['variations'] ) ? $_POST['variations'] : array();
        
        if ( ! $product_id ) {
            wp_send_json_error( array(
                'message' => __( 'Produto inválido.', 'wc-upsell' ),
            ) );
        }
        
        // Verify kit exists
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $kit = $product_kit->get_kit_by_quantity( $quantity );
        
        if ( ! $kit ) {
            wp_send_json_error( array(
                'message' => __( 'Kit não encontrado.', 'wc-upsell' ),
            ) );
        }
        
        $product = wc_get_product( $product_id );
        $is_variable = $product && $product->is_type( 'variable' );
        
        // If product is variable and we have variations, add each one separately
        if ( $is_variable && ! empty( $variations ) && is_array( $variations ) ) {
            $_POST['wc_upsell_kit'] = true;
            $_POST['wc_upsell_quantity'] = $quantity;
            
            $added_items = array();
            $unit_price = floatval( $kit['price'] ) / $quantity;
            
            foreach ( $variations as $variation_data ) {
                if ( ! isset( $variation_data['variation_id'] ) || ! isset( $variation_data['attributes'] ) ) {
                    continue;
                }
                
                $variation_id = absint( $variation_data['variation_id'] );
                $attributes = $variation_data['attributes'];
                
                // Store variation attributes for this item
                $_POST['wc_upsell_variation_attributes'] = $attributes;
                
                // Add single item with custom price
                $cart_item_key = WC()->cart->add_to_cart(
                    $product_id,
                    1, // Add 1 unit at a time
                    $variation_id,
                    $attributes
                );
                
                if ( $cart_item_key ) {
                    $added_items[] = $cart_item_key;
                    
                    // Set custom price for this cart item
                    WC()->cart->cart_contents[ $cart_item_key ]['wc_upsell_unit_price'] = $unit_price;
                }
            }
            
            if ( ! empty( $added_items ) ) {
                WC()->cart->calculate_totals();
                
                wp_send_json_success( array(
                    'message' => __( 'Kit adicionado ao carrinho!', 'wc-upsell' ),
                    'cart_hash' => WC()->cart->get_cart_hash(),
                    'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
                ) );
            }
        } else {
            // Simple product or no variations - add as before
            $_POST['wc_upsell_kit'] = true;
            $_POST['wc_upsell_quantity'] = $quantity;
            
            $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
            
            if ( $passed_validation ) {
                $cart_item_key = WC()->cart->add_to_cart(
                    $product_id,
                    $quantity,
                    0
                );
                
                if ( $cart_item_key ) {
                    do_action( 'wc_upsell_kit_added_to_cart', $cart_item_key, $product_id, $quantity );
                    
                    wp_send_json_success( array(
                        'message' => __( 'Kit adicionado ao carrinho!', 'wc-upsell' ),
                        'cart_hash' => WC()->cart->get_cart_hash(),
                        'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
                    ) );
                }
            }
        }
        
        wp_send_json_error( array(
            'message' => __( 'Não foi possível adicionar o kit ao carrinho.', 'wc-upsell' ),
        ) );
    }

    /**
     * Get cart contents with kit data
     *
     * @return array Cart contents
     */
    public function get_kit_cart_items() {
        $kit_items = array();
        
        if ( ! WC()->cart ) {
            return $kit_items;
        }
        
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['wc_upsell_kit'] ) ) {
                $kit_items[ $cart_item_key ] = $cart_item;
            }
        }
        
        return $kit_items;
    }

    /**
     * Check if cart has any kit items
     *
     * @return bool
     */
    public function cart_has_kits() {
        return ! empty( $this->get_kit_cart_items() );
    }

    /**
     * Remove kit data from cart item (if needed)
     *
     * @param string $cart_item_key Cart item key
     * @return bool
     */
    public function remove_kit_from_cart( $cart_item_key ) {
        $cart = WC()->cart->get_cart();
        
        if ( isset( $cart[ $cart_item_key ]['wc_upsell_kit'] ) ) {
            unset( $cart[ $cart_item_key ]['wc_upsell_kit'] );
            unset( $cart[ $cart_item_key ]['wc_upsell_unique_key'] );
            WC()->cart->set_cart_contents( $cart );
            return true;
        }
        
        return false;
    }
}
