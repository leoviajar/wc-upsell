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
        
        // Validate minimum quantity in cart and checkout
        add_filter( 'woocommerce_update_cart_validation', array( $this, 'validate_cart_quantity_minimum' ), 10, 4 );
        add_action( 'woocommerce_check_cart_items', array( $this, 'validate_cart_on_checkout' ) );
        add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'revert_invalid_quantity_update' ), 10, 4 );
        add_filter( 'woocommerce_update_cart_action_cart_updated', array( $this, 'force_cart_refresh_on_revert' ), 10, 1 );
        add_filter( 'wc_smart_checkout_quantity_input_min', array( $this, 'set_minimum_quantity_for_kit' ), 10, 2 );
        
        // AJAX handler for adding kit to cart
        add_action( 'wp_ajax_wc_upsell_add_kit', array( $this, 'ajax_add_kit_to_cart' ) );
        add_action( 'wp_ajax_nopriv_wc_upsell_add_kit', array( $this, 'ajax_add_kit_to_cart' ) );
        
        // Hook into smart checkout quantity change to enforce minimum
        add_action( 'wp_ajax_smart_checkout_quantity_chage', array( $this, 'block_kit_quantity_change' ), 5 );
        add_action( 'wp_ajax_nopriv_smart_checkout_quantity_chage', array( $this, 'block_kit_quantity_change' ), 5 );
        
        // Store item data before removal and handle cascade removal
        add_action( 'woocommerce_remove_cart_item', array( $this, 'store_item_before_removal' ), 10, 2 );
        add_action( 'woocommerce_cart_item_removed', array( $this, 'remove_remaining_kit_items' ), 10, 2 );
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
        if ( isset( $cart_item['wc_upsell_kit'] ) && isset( $cart_item['wc_upsell_unique_key'] ) ) {
            $kit_data = $cart_item['wc_upsell_kit'];
            $unique_key = $cart_item['wc_upsell_unique_key'];
            $product_id = $cart_item['product_id'];
            
            // Calculate the current total quantity for this kit group
            $total_quantity = 0;
            $cart = WC()->cart->get_cart();
            
            foreach ( $cart as $key => $item ) {
                if ( isset( $item['wc_upsell_unique_key'] ) && 
                     $item['wc_upsell_unique_key'] === $unique_key && 
                     $item['product_id'] === $product_id ) {
                    $total_quantity += $item['quantity'];
                }
            }
            
            $item_data[] = array(
                'key' => __( 'Kit', 'wc-upsell' ),
                'value' => sprintf( 
                    /* translators: %d: kit quantity */
                    __( '%d Unidades', 'wc-upsell' ), 
                    $total_quantity 
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
     * Validate minimum quantity when updating cart
     *
     * @param bool $passed Validation status
     * @param string $cart_item_key Cart item key
     * @param array $values Cart item values
     * @param int $quantity New quantity
     * @return bool
     */
    public function validate_cart_quantity_minimum( $passed, $cart_item_key, $values, $quantity ) {
        // Check if this is a kit item
        if ( ! isset( $values['wc_upsell_kit'] ) || ! isset( $values['wc_upsell_unique_key'] ) ) {
            return $passed;
        }
        
        $product_id = $values['product_id'];
        $unique_key = $values['wc_upsell_unique_key'];
        
        // Get all items in the same kit group
        $cart = WC()->cart->get_cart();
        $group_total_quantity = 0;
        
        foreach ( $cart as $key => $item ) {
            if ( isset( $item['wc_upsell_unique_key'] ) && 
                 $item['wc_upsell_unique_key'] === $unique_key && 
                 $item['product_id'] === $product_id ) {
                
                // Calculate new total for this group
                if ( $key === $cart_item_key ) {
                    $group_total_quantity += $quantity; // Use the new quantity
                } else {
                    $group_total_quantity += $item['quantity'];
                }
            }
        }
        
        // Get the minimum kit quantity for this product
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $min_kit_quantity = $product_kit->get_minimum_kit_quantity();
        
        // If there's a minimum and the new total is below it, prevent the update
        if ( $min_kit_quantity > 0 && $group_total_quantity < $min_kit_quantity ) {
            wc_add_notice( 
                sprintf(
                    /* translators: %d: minimum quantity */
                    __( 'A quantidade mínima para este kit é %d unidades. Não é possível reduzir abaixo deste valor.', 'wc-upsell' ),
                    $min_kit_quantity
                ),
                'error'
            );
            
            return false;
        }
        
        return $passed;
    }

    /**
     * Revert quantity update if it violates minimum kit quantity
     * This runs after the quantity has been updated to ensure visual consistency
     *
     * @param string $cart_item_key Cart item key
     * @param int $quantity New quantity
     * @param int $old_quantity Old quantity
     * @param WC_Cart $cart Cart object
     */
    public function revert_invalid_quantity_update( $cart_item_key, $quantity, $old_quantity, $cart ) {
        $cart_contents = $cart->get_cart();
        
        if ( ! isset( $cart_contents[ $cart_item_key ] ) ) {
            return;
        }
        
        $cart_item = $cart_contents[ $cart_item_key ];
        
        // Check if this is a kit item
        if ( ! isset( $cart_item['wc_upsell_kit'] ) || ! isset( $cart_item['wc_upsell_unique_key'] ) ) {
            return;
        }
        
        $product_id = $cart_item['product_id'];
        $unique_key = $cart_item['wc_upsell_unique_key'];
        
        // Calculate total quantity for this kit group with the new quantity
        $group_total_quantity = 0;
        foreach ( $cart_contents as $key => $item ) {
            if ( isset( $item['wc_upsell_unique_key'] ) && 
                 $item['wc_upsell_unique_key'] === $unique_key && 
                 $item['product_id'] === $product_id ) {
                $group_total_quantity += $item['quantity'];
            }
        }
        
        // Get the minimum kit quantity for this product
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $min_kit_quantity = $product_kit->get_minimum_kit_quantity();
        
        // If the new total is below minimum, revert to old quantity
        if ( $min_kit_quantity > 0 && $group_total_quantity < $min_kit_quantity ) {
            // Revert the quantity in the cart
            $cart->cart_contents[ $cart_item_key ]['quantity'] = $old_quantity;
            
            // Force update the cart session to reflect the revert
            $cart->set_cart_contents( $cart->cart_contents );
            
            // Set a flag for AJAX to know quantity was reverted
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                set_transient( 'wc_upsell_quantity_reverted_' . $cart_item_key, $old_quantity, 30 );
            }
            
            // Add notice to be displayed
            wc_add_notice( 
                sprintf(
                    /* translators: %d: minimum quantity */
                    __( 'A quantidade mínima para este kit é %d unidades. A quantidade não pode ser reduzida.', 'wc-upsell' ),
                    $min_kit_quantity
                ),
                'error'
            );
        }
    }

    /**
     * Force cart refresh when quantity was reverted
     *
     * @param bool $cart_updated Whether cart was updated
     * @return bool
     */
    public function force_cart_refresh_on_revert( $cart_updated ) {
        // Always return true to force page reload when there are notices
        if ( wc_notice_count( 'error' ) > 0 ) {
            return true;
        }
        return $cart_updated;
    }

    /**
     * Set minimum quantity for kit items in smart checkout
     *
     * @param int $min_value Minimum value
     * @param WC_Product $_product Product object
     * @return int
     */
    public function set_minimum_quantity_for_kit( $min_value, $_product ) {
        // Get current cart item being processed
        $cart = WC()->cart->get_cart();
        
        foreach ( $cart as $cart_item ) {
            if ( $cart_item['product_id'] === $_product->get_id() && 
                 isset( $cart_item['wc_upsell_kit'] ) && 
                 isset( $cart_item['wc_upsell_unique_key'] ) ) {
                
                // Get the minimum kit quantity for this product
                $product_kit = new WC_Upsell_Product_Kit( $_product->get_id() );
                $min_kit_quantity = $product_kit->get_minimum_kit_quantity();
                
                if ( $min_kit_quantity > 0 ) {
                    // Calculate total quantity for this kit group
                    $unique_key = $cart_item['wc_upsell_unique_key'];
                    $total_quantity = 0;
                    
                    foreach ( $cart as $item ) {
                        if ( isset( $item['wc_upsell_unique_key'] ) && 
                             $item['wc_upsell_unique_key'] === $unique_key && 
                             $item['product_id'] === $_product->get_id() ) {
                            $total_quantity += $item['quantity'];
                        }
                    }
                    
                    // Set the minimum to the kit minimum quantity
                    return max( $min_value, $min_kit_quantity );
                }
                
                break;
            }
        }
        
        return $min_value;
    }

    /**
     * Block kit quantity change if it violates minimum
     * This runs BEFORE smart checkout processes the request
     */
    public function block_kit_quantity_change() {
        if ( ! isset( $_POST['cart_item_key'], $_POST['quantity'] ) ) {
            return;
        }
        
        $cart_item_key = wc_clean( $_POST['cart_item_key'] );
        $new_quantity = intval( wc_clean( $_POST['quantity'] ) );
        
        $cart = WC()->cart->get_cart();
        
        if ( ! isset( $cart[ $cart_item_key ] ) ) {
            return;
        }
        
        $cart_item = $cart[ $cart_item_key ];
        
        // Check if this is a kit item
        if ( ! isset( $cart_item['wc_upsell_kit'] ) || ! isset( $cart_item['wc_upsell_unique_key'] ) ) {
            return;
        }
        
        $product_id = $cart_item['product_id'];
        $unique_key = $cart_item['wc_upsell_unique_key'];
        $old_quantity = $cart_item['quantity'];
        
        // Get the minimum kit quantity for this product
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $min_kit_quantity = $product_kit->get_minimum_kit_quantity();
        
        if ( $min_kit_quantity <= 0 ) {
            return;
        }
        
        // Calculate what the total would be with the new quantity
        $group_total_quantity = 0;
        foreach ( $cart as $key => $item ) {
            if ( isset( $item['wc_upsell_unique_key'] ) && 
                 $item['wc_upsell_unique_key'] === $unique_key && 
                 $item['product_id'] === $product_id ) {
                
                if ( $key === $cart_item_key ) {
                    $group_total_quantity += $new_quantity;
                } else {
                    $group_total_quantity += $item['quantity'];
                }
            }
        }
        
        // If below minimum, block and return error immediately
        if ( $group_total_quantity < $min_kit_quantity ) {
            wp_send_json_error(
                sprintf(
                    __( 'A quantidade mínima para este kit é %d unidades.', 'wc-upsell' ),
                    $min_kit_quantity
                )
            );
        }
    }

    /**
     * Remove remaining kit items after one is removed
     * This ensures all items from the same kit group are removed together
     *
     * @param string $cart_item_key Cart item key that was removed
     * @param WC_Cart $cart Cart object
     */
    public function remove_remaining_kit_items( $cart_item_key, $cart ) {
        // Get the removed item data from session (stored before removal)
        $removed_item = WC()->session->get( 'wc_upsell_removed_item_' . $cart_item_key );
        
        if ( ! $removed_item ) {
            return;
        }
        
        // Check if this is a kit item
        if ( ! isset( $removed_item['wc_upsell_kit'] ) || ! isset( $removed_item['wc_upsell_unique_key'] ) ) {
            WC()->session->set( 'wc_upsell_removed_item_' . $cart_item_key, null );
            return;
        }
        
        $product_id = $removed_item['product_id'];
        $unique_key = $removed_item['wc_upsell_unique_key'];
        
        // Get the minimum kit quantity
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $min_kit_quantity = $product_kit->get_minimum_kit_quantity();
        
        if ( $min_kit_quantity <= 0 ) {
            WC()->session->set( 'wc_upsell_removed_item_' . $cart_item_key, null );
            return;
        }
        
        // Calculate remaining quantity in the kit group
        $remaining_quantity = 0;
        $remaining_items = array();
        
        foreach ( $cart->get_cart() as $key => $item ) {
            if ( isset( $item['wc_upsell_unique_key'] ) && 
                 $item['wc_upsell_unique_key'] === $unique_key && 
                 $item['product_id'] === $product_id ) {
                
                $remaining_quantity += $item['quantity'];
                $remaining_items[] = $key;
            }
        }
        
        // If remaining quantity is below minimum, remove all remaining items
        if ( $remaining_quantity < $min_kit_quantity && count( $remaining_items ) > 0 ) {
            foreach ( $remaining_items as $key ) {
                $cart->remove_cart_item( $key );
            }
            
            wc_add_notice(
                __( 'Todo o kit foi removido do carrinho.', 'wc-upsell' ),
                'notice'
            );
        }
        
        // Clean up session
        WC()->session->set( 'wc_upsell_removed_item_' . $cart_item_key, null );
    }

    /**
     * Validate cart items on checkout to enforce minimum quantities
     */
    public function validate_cart_on_checkout() {
        if ( ! WC()->cart ) {
            return;
        }

        $cart = WC()->cart->get_cart();
        $processed_groups = array();

        foreach ( $cart as $cart_item_key => $cart_item ) {
            // Check if this is a kit item
            if ( ! isset( $cart_item['wc_upsell_kit'] ) || ! isset( $cart_item['wc_upsell_unique_key'] ) ) {
                continue;
            }

            $product_id = $cart_item['product_id'];
            $unique_key = $cart_item['wc_upsell_unique_key'];
            $group_key = $product_id . '_' . $unique_key;

            // Skip if we already processed this group
            if ( isset( $processed_groups[ $group_key ] ) ) {
                continue;
            }

            // Calculate total quantity for this group
            $group_total_quantity = 0;
            foreach ( $cart as $item ) {
                if ( isset( $item['wc_upsell_unique_key'] ) && 
                     $item['wc_upsell_unique_key'] === $unique_key && 
                     $item['product_id'] === $product_id ) {
                    $group_total_quantity += $item['quantity'];
                }
            }

            // Get the minimum kit quantity for this product
            $product_kit = new WC_Upsell_Product_Kit( $product_id );
            $min_kit_quantity = $product_kit->get_minimum_kit_quantity();

            // Validate minimum quantity
            if ( $min_kit_quantity > 0 && $group_total_quantity < $min_kit_quantity ) {
                wc_add_notice( 
                    sprintf(
                        /* translators: %d: minimum quantity */
                        __( 'A quantidade mínima para este kit é %d unidades. Por favor, ajuste a quantidade no carrinho antes de finalizar a compra.', 'wc-upsell' ),
                        $min_kit_quantity
                    ),
                    'error'
                );
            }

            // Mark this group as processed
            $processed_groups[ $group_key ] = true;
        }
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
    
    /**
     * Store item data before removal for cascade removal logic
     *
     * @param string $cart_item_key Cart item key being removed
     * @param WC_Cart $cart Cart object
     */
    public function store_item_before_removal( $cart_item_key, $cart ) {
        $cart_contents = $cart->get_cart();
        
        if ( isset( $cart_contents[ $cart_item_key ] ) ) {
            WC()->session->set( 'wc_upsell_removed_item_' . $cart_item_key, $cart_contents[ $cart_item_key ] );
        }
    }
}
