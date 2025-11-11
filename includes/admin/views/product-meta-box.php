<?php
/**
 * Product Meta Box Template
 *
 * @package WC_Upsell
 * @since 1.0.0
 * 
 * @var array $kits Array of kits
 * @var WP_Post $post Post object
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$product = wc_get_product( $post->ID );
$regular_price = $product ? $product->get_regular_price() : 0;
?>

<div class="wc-upsell-meta-box">
    <div class="wc-upsell-meta-header">
        <p class="description">
            <?php esc_html_e( 'Configure kits de upsell com descontos progressivos. Os kits serão exibidos na página do produto.', 'wc-upsell' ); ?>
        </p>
        <?php if ( $regular_price ) : ?>
            <p class="wc-upsell-product-price">
                <strong><?php esc_html_e( 'Preço regular do produto:', 'wc-upsell' ); ?></strong>
                <?php echo wc_price( $regular_price ); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="wc-upsell-kits-container">
        <table class="wc-upsell-kits-table widefat">
            <thead>
                <tr>
                    <th class="sort-column"></th>
                    <th class="quantity-column"><?php esc_html_e( 'Quantidade', 'wc-upsell' ); ?></th>
                    <th class="price-column"><?php esc_html_e( 'Preço do Kit', 'wc-upsell' ); ?></th>
                    <th class="unit-price-column"><?php esc_html_e( 'Preço/Uni', 'wc-upsell' ); ?></th>
                    <th class="discount-column"><?php esc_html_e( 'Desconto', 'wc-upsell' ); ?></th>
                    <th class="badge-column"><?php esc_html_e( 'Badge', 'wc-upsell' ); ?></th>
                    <th class="color-column"><?php esc_html_e( 'Cor', 'wc-upsell' ); ?></th>
                    <th class="enabled-column"><?php esc_html_e( 'Ativo', 'wc-upsell' ); ?></th>
                    <th class="actions-column"></th>
                </tr>
            </thead>
            <tbody id="wc-upsell-kits-list">
                <?php if ( ! empty( $kits ) ) : ?>
                    <?php foreach ( $kits as $index => $kit ) : 
                        $unit_price = $kit['quantity'] > 0 ? $kit['price'] / $kit['quantity'] : 0;
                        $discount = 0;
                        if ( $regular_price > 0 && $kit['quantity'] > 0 ) {
                            $normal_total = $regular_price * $kit['quantity'];
                            $discount = ( ( $normal_total - $kit['price'] ) / $normal_total ) * 100;
                        }
                    ?>
                    <tr class="wc-upsell-kit-row">
                        <td class="sort-handle">
                            <span class="dashicons dashicons-menu"></span>
                        </td>
                        <td>
                            <input type="number" 
                                   name="wc_upsell_kits[<?php echo $index; ?>][quantity]" 
                                   value="<?php echo esc_attr( $kit['quantity'] ); ?>"
                                   min="1"
                                   step="1"
                                   class="small-text kit-quantity"
                                   required />
                        </td>
                        <td>
                            <input type="number" 
                                   name="wc_upsell_kits[<?php echo $index; ?>][price]" 
                                   value="<?php echo esc_attr( $kit['price'] ); ?>"
                                   min="0"
                                   step="0.01"
                                   class="regular-text kit-price"
                                   required />
                        </td>
                        <td>
                            <span class="unit-price-display"><?php echo wc_price( $unit_price ); ?></span>
                        </td>
                        <td>
                            <span class="discount-display"><?php echo round( $discount, 1 ); ?>%</span>
                        </td>
                        <td>
                            <input type="text" 
                                   name="wc_upsell_kits[<?php echo $index; ?>][badge_text]" 
                                   value="<?php echo esc_attr( $kit['badge_text'] ); ?>"
                                   placeholder="<?php esc_attr_e( 'Mais Vendido', 'wc-upsell' ); ?>"
                                   class="regular-text" />
                        </td>
                        <td>
                            <input type="color" 
                                   name="wc_upsell_kits[<?php echo $index; ?>][badge_color]" 
                                   value="<?php echo esc_attr( $kit['badge_color'] ); ?>"
                                   class="kit-color-picker" />
                        </td>
                        <td>
                            <input type="checkbox" 
                                   name="wc_upsell_kits[<?php echo $index; ?>][enabled]" 
                                   value="yes"
                                   <?php checked( isset( $kit['enabled'] ) ? $kit['enabled'] : true, true ); ?> />
                        </td>
                        <td>
                            <button type="button" class="button button-small remove-kit">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="wc-upsell-actions">
            <button type="button" id="add-new-kit" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e( 'Adicionar Kit', 'wc-upsell' ); ?>
            </button>
        </div>
    </div>

    <div class="wc-upsell-meta-footer">
        <p class="description">
            <strong><?php esc_html_e( 'Dicas:', 'wc-upsell' ); ?></strong><br>
            • <?php esc_html_e( 'Configure múltiplos kits com diferentes quantidades', 'wc-upsell' ); ?><br>
            • <?php esc_html_e( 'Use badges para destacar ofertas (ex: "Mais Vendido", "Maior Desconto")', 'wc-upsell' ); ?><br>
            • <?php esc_html_e( 'O desconto é calculado automaticamente com base no preço regular', 'wc-upsell' ); ?><br>
            • <?php esc_html_e( 'Desabilite kits temporariamente sem removê-los', 'wc-upsell' ); ?>
        </p>
    </div>
</div>

<!-- Template para novo kit -->
<script type="text/template" id="wc-upsell-kit-template">
<tr class="wc-upsell-kit-row">
    <td class="sort-handle">
        <span class="dashicons dashicons-menu"></span>
    </td>
    <td>
        <input type="number" 
               name="wc_upsell_kits[{{INDEX}}][quantity]" 
               value="1"
               min="1"
               step="1"
               class="small-text kit-quantity"
               required />
    </td>
    <td>
        <input type="number" 
               name="wc_upsell_kits[{{INDEX}}][price]" 
               value="<?php echo esc_attr( $regular_price ); ?>"
               min="0"
               step="0.01"
               class="regular-text kit-price"
               required />
    </td>
    <td>
        <span class="unit-price-display"><?php echo wc_price( $regular_price ); ?></span>
    </td>
    <td>
        <span class="discount-display">0%</span>
    </td>
    <td>
        <input type="text" 
               name="wc_upsell_kits[{{INDEX}}][badge_text]" 
               value=""
               placeholder="<?php esc_attr_e( 'Mais Vendido', 'wc-upsell' ); ?>"
               class="regular-text" />
    </td>
    <td>
        <input type="color" 
               name="wc_upsell_kits[{{INDEX}}][badge_color]" 
               value="#000000"
               class="kit-color-picker" />
    </td>
    <td>
        <input type="checkbox" 
               name="wc_upsell_kits[{{INDEX}}][enabled]" 
               value="yes"
               checked />
    </td>
    <td>
        <button type="button" class="button button-small remove-kit">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </td>
</tr>
</script>

<style>
.wc-upsell-meta-box {
    margin: 0 -12px;
}

.wc-upsell-meta-header {
    padding: 12px;
    background: #f0f0f1;
    border-bottom: 1px solid #ddd;
}

.wc-upsell-product-price {
    margin-top: 10px;
    padding: 8px;
    background: #fff;
    border-left: 3px solid #2271b1;
}

.wc-upsell-kits-container {
    padding: 12px;
}

.wc-upsell-kits-table {
    margin-bottom: 15px;
}

.wc-upsell-kits-table th {
    font-weight: 600;
    background: #f9f9f9;
}

.wc-upsell-kits-table td {
    vertical-align: middle;
}

.sort-column {
    width: 30px;
}

.quantity-column {
    width: 80px;
}

.price-column {
    width: 120px;
}

.unit-price-column {
    width: 100px;
}

.discount-column {
    width: 80px;
}

.badge-column {
    width: 150px;
}

.color-column {
    width: 60px;
}

.enabled-column {
    width: 50px;
    text-align: center;
}

.actions-column {
    width: 50px;
}

.sort-handle {
    cursor: move;
    text-align: center;
    color: #999;
}

.sort-handle:hover {
    color: #333;
}

.unit-price-display,
.discount-display {
    font-weight: 600;
    color: #2271b1;
}

.kit-color-picker {
    width: 50px;
    height: 30px;
    border: 1px solid #ddd;
    cursor: pointer;
}

.wc-upsell-actions {
    margin-top: 10px;
}

.wc-upsell-meta-footer {
    padding: 12px;
    background: #f0f0f1;
    border-top: 1px solid #ddd;
}

.remove-kit {
    color: #b32d2e;
}

.remove-kit:hover {
    color: #dc3232;
    border-color: #dc3232;
}
</style>
