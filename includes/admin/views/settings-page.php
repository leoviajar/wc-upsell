<?php
/**
 * Settings Page Template
 *
 * @package WC_Upsell
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get all products with kits
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_wc_upsell_kits',
            'compare' => 'EXISTS',
        ),
    ),
);

$products_with_kits = get_posts( $args );
?>

<div class="wrap wc-upsell-settings">
    <h1><?php esc_html_e( 'Upsell Kits - Configurações', 'wc-upsell' ); ?></h1>
    
    <div class="wc-upsell-header">
        <p class="description">
            <?php esc_html_e( 'Configure kits de upsell para seus produtos. Os kits permitem que você crie ofertas com descontos progressivos baseados na quantidade.', 'wc-upsell' ); ?>
        </p>
    </div>

    <div class="wc-upsell-dashboard">
        <div class="wc-upsell-card">
            <h2><?php esc_html_e( 'Visão Geral', 'wc-upsell' ); ?></h2>
            
            <div class="wc-upsell-stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo count( $products_with_kits ); ?></span>
                    <span class="stat-label"><?php esc_html_e( 'Produtos com Kits', 'wc-upsell' ); ?></span>
                </div>
                
                <div class="stat-box">
                    <span class="stat-number">
                        <?php
                        $total_kits = 0;
                        foreach ( $products_with_kits as $product ) {
                            $product_kit = new WC_Upsell_Product_Kit( $product->ID );
                            $total_kits += count( $product_kit->get_enabled_kits() );
                        }
                        echo $total_kits;
                        ?>
                    </span>
                    <span class="stat-label"><?php esc_html_e( 'Total de Kits Ativos', 'wc-upsell' ); ?></span>
                </div>
            </div>
        </div>

        <div class="wc-upsell-card">
            <h2><?php esc_html_e( 'Como Usar', 'wc-upsell' ); ?></h2>
            
            <ol class="wc-upsell-instructions">
                <li><?php esc_html_e( 'Vá até a página de edição de qualquer produto', 'wc-upsell' ); ?></li>
                <li><?php esc_html_e( 'Role até a meta box "Upsell Kits"', 'wc-upsell' ); ?></li>
                <li><?php esc_html_e( 'Adicione os kits desejados com quantidade e preço', 'wc-upsell' ); ?></li>
                <li><?php esc_html_e( 'Configure badges personalizados (opcional)', 'wc-upsell' ); ?></li>
                <li><?php esc_html_e( 'Salve o produto e visualize na página do produto', 'wc-upsell' ); ?></li>
            </ol>
        </div>

        <?php if ( ! empty( $products_with_kits ) ) : ?>
        <div class="wc-upsell-card">
            <h2><?php esc_html_e( 'Produtos com Kits Configurados', 'wc-upsell' ); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Produto', 'wc-upsell' ); ?></th>
                        <th><?php esc_html_e( 'Kits Ativos', 'wc-upsell' ); ?></th>
                        <th><?php esc_html_e( 'Ações', 'wc-upsell' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $products_with_kits as $product ) : 
                        $product_kit = new WC_Upsell_Product_Kit( $product->ID );
                        $kits = $product_kit->get_enabled_kits();
                    ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo get_edit_post_link( $product->ID ); ?>">
                                    <?php echo esc_html( get_the_title( $product->ID ) ); ?>
                                </a>
                            </strong>
                        </td>
                        <td>
                            <?php echo count( $kits ); ?> <?php esc_html_e( 'kit(s)', 'wc-upsell' ); ?>
                            <div class="kit-summary">
                                <?php foreach ( $kits as $kit ) : ?>
                                    <span class="kit-tag">
                                        <?php echo $kit['quantity']; ?>x - <?php echo wc_price( $kit['price'] ); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link( $product->ID ); ?>" class="button button-small">
                                <?php esc_html_e( 'Editar', 'wc-upsell' ); ?>
                            </a>
                            <a href="<?php echo get_permalink( $product->ID ); ?>" class="button button-small" target="_blank">
                                <?php esc_html_e( 'Visualizar', 'wc-upsell' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="wc-upsell-card">
            <h2><?php esc_html_e( 'Informações do Plugin', 'wc-upsell' ); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Versão', 'wc-upsell' ); ?></th>
                    <td><?php echo esc_html( WC_UPSELL_VERSION ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Compatibilidade HPOS', 'wc-upsell' ); ?></th>
                    <td>
                        <span class="dashicons dashicons-yes" style="color: green;"></span>
                        <?php esc_html_e( 'Ativada', 'wc-upsell' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'WooCommerce', 'wc-upsell' ); ?></th>
                    <td><?php echo esc_html( WC()->version ); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
.wc-upsell-settings {
    max-width: 1200px;
}

.wc-upsell-header {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-left: 4px solid #2271b1;
}

.wc-upsell-dashboard {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-top: 20px;
}

.wc-upsell-card {
    background: #fff;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.wc-upsell-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.wc-upsell-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-box {
    background: #f0f0f1;
    padding: 20px;
    text-align: center;
    border-radius: 4px;
}

.stat-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
}

.stat-label {
    display: block;
    margin-top: 5px;
    color: #666;
}

.wc-upsell-instructions {
    line-height: 2;
}

.kit-summary {
    margin-top: 5px;
}

.kit-tag {
    display: inline-block;
    background: #f0f0f1;
    padding: 2px 8px;
    margin-right: 5px;
    border-radius: 3px;
    font-size: 12px;
}
</style>
