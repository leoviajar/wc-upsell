<?php
/**
 * Admin Class
 *
 * Handles all admin functionality
 *
 * @package WC_Upsell
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Upsell_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        // Add menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 99 );
        
        // Add product meta box
        add_action( 'add_meta_boxes', array( $this, 'add_product_meta_box' ) );
        
        // Save product meta box
        add_action( 'save_post_product', array( $this, 'save_product_meta_box' ), 10, 2 );
        
        // AJAX handlers
        add_action( 'wp_ajax_wc_upsell_save_kit', array( $this, 'ajax_save_kit' ) );
        add_action( 'wp_ajax_wc_upsell_delete_kit', array( $this, 'ajax_delete_kit' ) );
        add_action( 'wp_ajax_wc_upsell_get_product_kits', array( $this, 'ajax_get_product_kits' ) );
        
        // Add products column
        add_filter( 'manage_product_posts_columns', array( $this, 'add_product_column' ) );
        add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), 10, 2 );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Upsell Kits', 'wc-upsell' ),
            __( 'Upsell Kits', 'wc-upsell' ),
            'manage_woocommerce',
            'wc-upsell-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include WC_UPSELL_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }

    /**
     * Add product meta box
     */
    public function add_product_meta_box() {
        add_meta_box(
            'wc_upsell_product_kits',
            __( 'Upsell Kits', 'wc-upsell' ),
            array( $this, 'render_product_meta_box' ),
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render product meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_product_meta_box( $post ) {
        $product_kit = new WC_Upsell_Product_Kit( $post->ID );
        $kits = $product_kit->get_kits();
        
        wp_nonce_field( 'wc_upsell_product_meta_box', 'wc_upsell_meta_box_nonce' );
        
        include WC_UPSELL_PLUGIN_DIR . 'includes/admin/views/product-meta-box.php';
    }

    /**
     * Save product meta box
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function save_product_meta_box( $post_id, $post ) {
        // Check nonce
        if ( ! isset( $_POST['wc_upsell_meta_box_nonce'] ) || 
             ! wp_verify_nonce( $_POST['wc_upsell_meta_box_nonce'], 'wc_upsell_product_meta_box' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save kits
        if ( isset( $_POST['wc_upsell_kits'] ) && is_array( $_POST['wc_upsell_kits'] ) ) {
            $kits = array();
            
            foreach ( $_POST['wc_upsell_kits'] as $kit_data ) {
                if ( ! empty( $kit_data['quantity'] ) && ! empty( $kit_data['price'] ) ) {
                    $kits[] = array(
                        'quantity' => absint( $kit_data['quantity'] ),
                        'price' => floatval( $kit_data['price'] ),
                        'badge_text' => isset( $kit_data['badge_text'] ) ? sanitize_text_field( $kit_data['badge_text'] ) : '',
                        'badge_color' => isset( $kit_data['badge_color'] ) ? sanitize_hex_color( $kit_data['badge_color'] ) : '#000000',
                        'enabled' => isset( $kit_data['enabled'] ) && $kit_data['enabled'] === 'yes',
                    );
                }
            }
            
            update_post_meta( $post_id, '_wc_upsell_kits', $kits );
        } else {
            delete_post_meta( $post_id, '_wc_upsell_kits' );
        }
    }

    /**
     * AJAX handler to save kit
     */
    public function ajax_save_kit() {
        check_ajax_referer( 'wc-upsell-admin-nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'wc-upsell' ) ) );
        }
        
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $kit_data = isset( $_POST['kit_data'] ) ? $_POST['kit_data'] : array();
        
        if ( ! $product_id || empty( $kit_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Dados inválidos.', 'wc-upsell' ) ) );
        }
        
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $result = $product_kit->save_kit( $kit_data );
        
        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Kit salvo com sucesso!', 'wc-upsell' ),
                'kits' => $product_kit->get_kits(),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Erro ao salvar kit.', 'wc-upsell' ) ) );
        }
    }

    /**
     * AJAX handler to delete kit
     */
    public function ajax_delete_kit() {
        check_ajax_referer( 'wc-upsell-admin-nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'wc-upsell' ) ) );
        }
        
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 0;
        
        if ( ! $product_id || ! $quantity ) {
            wp_send_json_error( array( 'message' => __( 'Dados inválidos.', 'wc-upsell' ) ) );
        }
        
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        $result = $product_kit->delete_kit( $quantity );
        
        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Kit removido com sucesso!', 'wc-upsell' ),
                'kits' => $product_kit->get_kits(),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Erro ao remover kit.', 'wc-upsell' ) ) );
        }
    }

    /**
     * AJAX handler to get product kits
     */
    public function ajax_get_product_kits() {
        check_ajax_referer( 'wc-upsell-admin-nonce', 'nonce' );
        
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        
        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Produto inválido.', 'wc-upsell' ) ) );
        }
        
        $product_kit = new WC_Upsell_Product_Kit( $product_id );
        
        wp_send_json_success( array(
            'kits' => $product_kit->get_kits(),
        ) );
    }

    /**
     * Add product column
     *
     * @param array $columns Columns
     * @return array Modified columns
     */
    public function add_product_column( $columns ) {
        $new_columns = array();
        
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            
            if ( $key === 'price' ) {
                $new_columns['wc_upsell_kits'] = __( 'Upsell Kits', 'wc-upsell' );
            }
        }
        
        return $new_columns;
    }

    /**
     * Render product column
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_product_column( $column, $post_id ) {
        if ( $column === 'wc_upsell_kits' ) {
            $product_kit = new WC_Upsell_Product_Kit( $post_id );
            $kits = $product_kit->get_enabled_kits();
            
            if ( empty( $kits ) ) {
                echo '<span class="na">—</span>';
            } else {
                echo '<span class="wc-upsell-kit-count">' . count( $kits ) . ' ' . __( 'kit(s)', 'wc-upsell' ) . '</span>';
            }
        }
    }
}
