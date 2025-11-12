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
        
        // Generate a unique key for this entire kit purchase
        $kit_unique_key = md5( microtime() . rand() );
        
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
                    'wc_upsell_unique_key' => $kit_unique_key,
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
        
        // Group cart items by product and unique kit key
        $product_groups = array();
        
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['wc_upsell_kit'] ) && isset( $cart_item['wc_upsell_unique_key'] ) ) {
                $product_id = $cart_item['product_id'];
                $unique_key = $cart_item['wc_upsell_unique_key'];
                $group_key = $product_id . '_' . $unique_key;
                
                if ( ! isset( $product_groups[$group_key] ) ) {
                    $product_groups[$group_key] = array(
                        'product_id' => $product_id,
                        'items' => array(),
                        'total_quantity' => 0,
                    );
                }
                
                $product_groups[$group_key]['items'][$cart_item_key] = $cart_item;
                $product_groups[$group_key]['total_quantity'] += $cart_item['quantity'];
            }
        }
        
        // Apply progressive pricing to each group
        foreach ( $product_groups as $group ) {
            $product_id = $group['product_id'];
            $total_quantity = $group['total_quantity'];
            
            $product_kit = new WC_Upsell_Product_Kit( $product_id );
            $kits = $product_kit->get_enabled_kits();
            
            if ( empty( $kits ) ) {
                continue;
            }
            
            // Find the best matching kit for the total quantity
            $matching_kit = null;
            $max_kit = null;
            
            foreach ( $kits as $kit ) {
                // Track the highest quantity kit
                if ( ! $max_kit || $kit['quantity'] > $max_kit['quantity'] ) {
                    $max_kit = $kit;
                }
                
                // Find exact or closest lower match
                if ( $kit['quantity'] <= $total_quantity ) {
                    if ( ! $matching_kit || $kit['quantity'] > $matching_kit['quantity'] ) {
                        $matching_kit = $kit;
                    }
                }
            }
            
            // Calculate unit price
            $unit_price = 0;
            
            if ( $matching_kit ) {
                // If we found a matching kit, use its pricing
                $kit_quantity = $matching_kit['quantity'];
                $kit_price = floatval( $matching_kit['price'] );
                
                if ( $total_quantity <= $kit_quantity ) {
                    // Exact match or less
                    $unit_price = $kit_price / $kit_quantity;
                } else {
                    // More items than the kit: apply kit price + extra items at unit price
                    $extra_items = $total_quantity - $kit_quantity;
                    $unit_price_for_extra = $kit_price / $kit_quantity;
                    $total_price = $kit_price + ( $extra_items * $unit_price_for_extra );
                    $unit_price = $total_price / $total_quantity;
                }
            } elseif ( $max_kit ) {
                // No matching kit (quantity lower than smallest kit or higher than largest)
                // Use the highest kit's unit price
                $unit_price = floatval( $max_kit['price'] ) / $max_kit['quantity'];
            }
            
            // Apply the calculated unit price to all items in this group
            if ( $unit_price > 0 ) {
                foreach ( $group['items'] as $cart_item_key => $cart_item ) {
                    $cart->cart_contents[$cart_item_key]['data']->set_price( $unit_price );
                }
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
     * Add to cart via AJAX
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
