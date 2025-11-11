<?php
/**
 * Kit Selector Template
 *
 * @package WC_Upsell
 * @since 1.0.0
 * 
 * @var WC_Product $product Product object
 * @var array $kits Array of kits
 * @var float $regular_price Regular price
 * @var WC_Upsell_Pricing_Engine $pricing_engine Pricing engine instance
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if product is variable
$is_variable = $product->is_type( 'variable' );
$available_variations = array();

if ( $is_variable ) {
    $available_variations = $product->get_available_variations();
}
?>

<div class="wc-upsell-kit-selector" 
     data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
     data-is-variable="<?php echo $is_variable ? '1' : '0'; ?>">
    
    <h3 class="wc-upsell-title"><?php esc_html_e( 'Selecione seu Kit', 'wc-upsell' ); ?></h3>
    
    <div class="wc-upsell-kits-grid">
        <?php foreach ( $kits as $index => $kit ) : 
            $quantity = isset( $kit['quantity'] ) ? absint( $kit['quantity'] ) : 1;
            $kit_price = isset( $kit['price'] ) ? floatval( $kit['price'] ) : 0;
            $unit_price = $quantity > 0 ? $kit_price / $quantity : 0;
            $savings = $pricing_engine->calculate_savings( $product->get_id(), $quantity );
            $discount_percentage = $pricing_engine->calculate_discount_percentage( $product->get_id(), $quantity );
            
            // Ensure regular_price is numeric
            $regular_price_value = floatval( $regular_price );
            $normal_total = $regular_price_value * $quantity;
            
            $is_first = $index === 0;
        ?>
        
        <div class="wc-upsell-kit-option <?php echo $is_first ? 'selected' : ''; ?>" 
             data-quantity="<?php echo esc_attr( $quantity ); ?>"
             data-price="<?php echo esc_attr( $kit_price ); ?>"
             data-kit-index="<?php echo esc_attr( $index ); ?>">
            
            <?php if ( ! empty( $kit['badge_text'] ) ) : ?>
            <div class="wc-upsell-badge" style="background-color: <?php echo esc_attr( $kit['badge_color'] ); ?>;">
                <?php echo esc_html( $kit['badge_text'] ); ?>
            </div>
            <?php endif; ?>
            
            <!-- Main Kit Info (Quantity + Price) -->
            <div class="wc-upsell-kit-main">
                <label class="wc-upsell-radio">
                    <input type="radio" 
                           name="wc_upsell_selected_kit" 
                           value="<?php echo esc_attr( $quantity ); ?>"
                           <?php checked( $is_first, true ); ?> />
                    <span class="wc-upsell-quantity">
                        <?php 
                        /* translators: %d: quantity */
                        echo sprintf( _n( '%d Unidade', '%d Unidades', $quantity, 'wc-upsell' ), $quantity ); 
                        ?>
                    </span>
                </label>
                
                <div class="wc-upsell-kit-pricing">
                    <div class="wc-upsell-price-main">
                        <?php echo wc_price( $kit_price ); ?>
                    </div>
                    
                    <?php if ( $savings > 0 ) : ?>
                    <div class="wc-upsell-price-regular">
                        <?php echo wc_price( $normal_total ); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ( $is_variable ) : ?>
            <!-- Variation Selector -->
            <div class="wc-upsell-variations-container" style="display: <?php echo $is_first ? 'block' : 'none'; ?>;">
                <div class="wc-upsell-variations-list">
                    <?php for ( $i = 1; $i <= $quantity; $i++ ) : ?>
                    <div class="wc-upsell-variation-item">
                        <div class="wc-upsell-variation-number"><?php echo $i; ?>.</div>
                        <div class="wc-upsell-variation-selects">
                            <?php
                            // Get product attributes
                            $attributes = $product->get_variation_attributes();
                            
                            foreach ( $attributes as $attribute_name => $options ) :
                                $attribute_label = wc_attribute_label( $attribute_name );
                                $sanitized_name = sanitize_title( $attribute_name );
                            ?>
                            <div class="wc-upsell-variation-field">
                                <label><?php echo esc_html( $attribute_label ); ?></label>
                                <select name="wc_upsell_variation[<?php echo $index; ?>][<?php echo $i - 1; ?>][<?php echo esc_attr( $sanitized_name ); ?>]"
                                        class="wc-upsell-variation-select"
                                        data-attribute="<?php echo esc_attr( $sanitized_name ); ?>"
                                        required>
                                    <option value=""><?php echo esc_html( sprintf( __( 'Escolha %s', 'wc-upsell' ), $attribute_label ) ); ?></option>
                                    <?php foreach ( $options as $option ) : ?>
                                        <option value="<?php echo esc_attr( $option ); ?>">
                                            <?php echo esc_html( $option ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <?php endforeach; ?>
    </div>
    
    <?php if ( $is_variable ) : ?>
    <!-- Store variations data for JavaScript -->
    <script type="application/json" id="wc-upsell-variations-data">
        <?php echo wp_json_encode( $available_variations ); ?>
    </script>
    <?php endif; ?>
    
    <input type="hidden" name="wc_upsell_kit" value="1" />
    <input type="hidden" name="wc_upsell_quantity" id="wc-upsell-selected-quantity" value="<?php echo esc_attr( $kits[0]['quantity'] ); ?>" />
</div>
