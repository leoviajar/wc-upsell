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
?>

<div class="wc-upsell-kit-selector" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
    <h3 class="wc-upsell-title"><?php esc_html_e( 'Selecione seu Kit', 'wc-upsell' ); ?></h3>
    
    <div class="wc-upsell-kits-grid">
        <?php foreach ( $kits as $index => $kit ) : 
            $quantity = $kit['quantity'];
            $kit_price = $kit['price'];
            $unit_price = $kit_price / $quantity;
            $savings = $pricing_engine->calculate_savings( $product->get_id(), $quantity );
            $discount_percentage = $pricing_engine->calculate_discount_percentage( $product->get_id(), $quantity );
            $normal_total = $regular_price * $quantity;
            
            $is_first = $index === 0;
        ?>
        
        <div class="wc-upsell-kit-option <?php echo $is_first ? 'selected' : ''; ?>" 
             data-quantity="<?php echo esc_attr( $quantity ); ?>"
             data-price="<?php echo esc_attr( $kit_price ); ?>">
            
            <?php if ( ! empty( $kit['badge_text'] ) ) : ?>
            <div class="wc-upsell-badge" style="background-color: <?php echo esc_attr( $kit['badge_color'] ); ?>;">
                <?php echo esc_html( $kit['badge_text'] ); ?>
            </div>
            <?php endif; ?>
            
            <div class="wc-upsell-kit-content">
                <div class="wc-upsell-kit-header">
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
                    
                    <?php if ( $discount_percentage > 0 ) : ?>
                    <span class="wc-upsell-unit-price">
                        <?php echo wc_price( $unit_price ); ?> / <?php esc_html_e( 'Uni.', 'wc-upsell' ); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
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
                
                <?php if ( $savings > 0 ) : ?>
                <div class="wc-upsell-savings">
                    <?php 
                    /* translators: 1: savings amount, 2: discount percentage */
                    echo sprintf( 
                        __( 'Economize %1$s (%2$s)', 'wc-upsell' ), 
                        wc_price( $savings ),
                        round( $discount_percentage, 1 ) . '%'
                    ); 
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php endforeach; ?>
    </div>
    
    <input type="hidden" name="wc_upsell_kit" value="1" />
    <input type="hidden" name="wc_upsell_quantity" id="wc-upsell-selected-quantity" value="<?php echo esc_attr( $kits[0]['quantity'] ); ?>" />
</div>
