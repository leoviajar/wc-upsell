<?php
/**
 * Settings Class
 *
 * Handles plugin settings page
 *
 * @package WC_Upsell
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Upsell_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts' ) );
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Configurações Upsell Kits', 'wc-upsell' ),
            __( 'Upsell Kits', 'wc-upsell' ),
            'manage_woocommerce',
            'wc-upsell-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Appearance settings
        register_setting( 'wc_upsell_appearance', 'wc_upsell_border_color' );
        register_setting( 'wc_upsell_appearance', 'wc_upsell_border_color_selected' );
        register_setting( 'wc_upsell_appearance', 'wc_upsell_bg_color' );
        register_setting( 'wc_upsell_appearance', 'wc_upsell_bg_color_selected' );
        register_setting( 'wc_upsell_appearance', 'wc_upsell_text_color' );
        register_setting( 'wc_upsell_appearance', 'wc_upsell_badge_bg_color' );
        register_setting( 'wc_upsell_appearance', 'wc_upsell_badge_text_color' );
        register_setting( 'wc_upsell_appearance', 'wc_upsell_unit_price_bg_color' );
        register_setting( 'wc_upsell_appearance', 'wc_upsell_unit_price_text_color' );
    }

    /**
     * Enqueue settings scripts
     */
    public function enqueue_settings_scripts( $hook ) {
        // Verifica se estamos na página de configurações
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wc-upsell-settings' ) {
            return;
        }

        // Enqueue color picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        // Add inline CSS for preview
        wp_add_inline_style( 'wp-color-picker', "
            #wc-upsell-preview .wc-upsell-kit-option {
                cursor: pointer;
                transition: all 0.2s ease;
            }
            #wc-upsell-preview .wc-upsell-variations-container {
                overflow: hidden;
            }
        ");
        
        // Add inline JavaScript
        wp_add_inline_script( 'wp-color-picker', "
            jQuery(document).ready(function($) {
                console.log('WC Upsell Settings JS loaded');
                
                // Initialize color pickers
                $('.wc-upsell-color-picker').wpColorPicker({
                    change: function(event, ui) {
                        updatePreview();
                    },
                    clear: function() {
                        updatePreview();
                    }
                });
                
                // Update preview function
                function updatePreview() {
                    var preview = $('#wc-upsell-preview');
                    
                    var borderColor = $('#wc_upsell_border_color').val();
                    var bgColor = $('#wc_upsell_bg_color').val();
                    var textColor = $('#wc_upsell_text_color').val();
                    var borderColorSelected = $('#wc_upsell_border_color_selected').val();
                    var bgColorSelected = $('#wc_upsell_bg_color_selected').val();
                    var badgeBgColor = $('#wc_upsell_badge_bg_color').val();
                    var badgeTextColor = $('#wc_upsell_badge_text_color').val();
                    var unitPriceBgColor = $('#wc_upsell_unit_price_bg_color').val();
                    var unitPriceTextColor = $('#wc_upsell_unit_price_text_color').val();
                    
                    if (borderColor) preview.find('.wc-upsell-kit-option').css('border-color', borderColor);
                    if (bgColor) preview.find('.wc-upsell-kit-option').css('background-color', bgColor);
                    if (textColor) preview.find('.wc-upsell-kit-option').css('color', textColor);
                    if (borderColorSelected) preview.find('.wc-upsell-kit-option.selected').css('border-color', borderColorSelected);
                    if (bgColorSelected) preview.find('.wc-upsell-kit-option.selected').css('background-color', bgColorSelected);
                    if (badgeBgColor) preview.find('.wc-upsell-badge').css('background-color', badgeBgColor);
                    if (badgeTextColor) preview.find('.wc-upsell-badge').css('color', badgeTextColor);
                    if (unitPriceBgColor) preview.find('.wc-upsell-unit-price-badge').css('background-color', unitPriceBgColor);
                    if (unitPriceTextColor) preview.find('.wc-upsell-unit-price-badge').css('color', unitPriceTextColor);
                }
                
                // Handle card click to toggle variations
                $(document).on('click', '#wc-upsell-preview .wc-upsell-kit-option', function(e) {
                    e.preventDefault();
                    var \$option = $(this);
                    var \$radio = \$option.find('input[type=\\\"radio\\\"]');
                    
                    console.log('Card clicked');
                    
                    // Unselect all
                    $('#wc-upsell-preview .wc-upsell-kit-option').removeClass('selected');
                    $('#wc-upsell-preview .wc-upsell-kit-option input[type=\\\"radio\\\"]').prop('checked', false);
                    $('#wc-upsell-preview .wc-upsell-variations-container').slideUp(200);
                    
                    // Select this one
                    \$option.addClass('selected');
                    \$radio.prop('checked', true);
                    \$option.find('.wc-upsell-variations-container').slideDown(200);
                    
                    updatePreview();
                });
                
                // Initial preview update
                setTimeout(function() {
                    updatePreview();
                }, 100);
            });
        " );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get current values or defaults
        $border_color = get_option( 'wc_upsell_border_color', '#ddd' );
        $border_color_selected = get_option( 'wc_upsell_border_color_selected', '#ff6600' );
        $bg_color = get_option( 'wc_upsell_bg_color', '#fff' );
        $bg_color_selected = get_option( 'wc_upsell_bg_color_selected', '#efefef' );
        $text_color = get_option( 'wc_upsell_text_color', '#333' );
        $badge_bg_color = get_option( 'wc_upsell_badge_bg_color', '#000' );
        $badge_text_color = get_option( 'wc_upsell_badge_text_color', '#fff' );
        $unit_price_bg_color = get_option( 'wc_upsell_unit_price_bg_color', '#ff6600' );
        $unit_price_text_color = get_option( 'wc_upsell_unit_price_text_color', '#fff' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div style="display: flex; gap: 30px; margin-top: 20px;">
                <!-- Settings Form -->
                <div style="flex: 1;">
                    <form method="post" action="options.php">
                        <?php settings_fields( 'wc_upsell_appearance' ); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_border_color"><?php _e( 'Cor da Borda', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_border_color" name="wc_upsell_border_color" value="<?php echo esc_attr( $border_color ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_border_color_selected"><?php _e( 'Cor da Borda (Selecionado)', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_border_color_selected" name="wc_upsell_border_color_selected" value="<?php echo esc_attr( $border_color_selected ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_bg_color"><?php _e( 'Cor de Fundo', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_bg_color" name="wc_upsell_bg_color" value="<?php echo esc_attr( $bg_color ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_bg_color_selected"><?php _e( 'Cor de Fundo (Selecionado)', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_bg_color_selected" name="wc_upsell_bg_color_selected" value="<?php echo esc_attr( $bg_color_selected ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_text_color"><?php _e( 'Cor do Texto', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_text_color" name="wc_upsell_text_color" value="<?php echo esc_attr( $text_color ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_badge_bg_color"><?php _e( 'Cor de Fundo do Badge', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_badge_bg_color" name="wc_upsell_badge_bg_color" value="<?php echo esc_attr( $badge_bg_color ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_badge_text_color"><?php _e( 'Cor do Texto do Badge', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_badge_text_color" name="wc_upsell_badge_text_color" value="<?php echo esc_attr( $badge_text_color ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_unit_price_bg_color"><?php _e( 'Cor de Fundo Preço/Uni', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_unit_price_bg_color" name="wc_upsell_unit_price_bg_color" value="<?php echo esc_attr( $unit_price_bg_color ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="wc_upsell_unit_price_text_color"><?php _e( 'Cor do Texto Preço/Uni', 'wc-upsell' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wc_upsell_unit_price_text_color" name="wc_upsell_unit_price_text_color" value="<?php echo esc_attr( $unit_price_text_color ); ?>" class="wc-upsell-color-picker" />
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <!-- Preview -->
                <div style="flex: 1; position: sticky; top: 32px; height: fit-content;">
                    <h2><?php _e( 'Preview', 'wc-upsell' ); ?></h2>
                    <div id="wc-upsell-preview" style="padding: 20px; background: #f5f5f5; border-radius: 8px;">
                        <div class="wc-upsell-kit-option selected" style="position: relative; border: 2px solid; border-radius: 16px; padding: 0; margin-bottom: 15px; cursor: pointer;">
                            <div class="wc-upsell-badge" style="position: absolute; top: -10px; right: 15px; padding: 4px 12px; font-size: 10px; font-weight: 600; text-transform: uppercase; border-radius: 4px; z-index: 10;">
                                MAIS VENDIDO
                            </div>
                            <div>
                                <div style="padding: 20px 25px; border-bottom: 1px solid #f0f0f0;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="radio" checked style="width: 16px; height: 16px; pointer-events: none;" />
                                            <span style="font-size: 18px; font-weight: 600;">3 Unidades</span>
                                            <span class="wc-upsell-unit-price-badge" style="padding: 3px 8px; font-size: 11px; font-weight: 600; border-radius: 4px;">
                                                R$ 66,33/un
                                            </span>
                                        </div>
                                        <div>
                                            <div style="font-size: 18px; font-weight: 700;">R$ 199,00</div>
                                            <div style="font-size: 14px; text-decoration: line-through; color: #999;">R$ 219,00</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="wc-upsell-variations-container" style="padding: 20px 25px; background: #fafafa; border-top: 1px solid #e0e0e0; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                                    <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e0e0e0;">
                                        <span style="font-size: 10px; width: 20px;"></span>
                                        <span style="flex: 1; font-size: 11px; font-weight: 700; color: #333; text-transform: uppercase;">COR</span>
                                        <span style="flex: 1; font-size: 11px; font-weight: 700; color: #333; text-transform: uppercase;">TAMANHO</span>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <div style="display: flex; gap: 12px; align-items: center;">
                                            <span style="font-size: 10px; width: 20px; font-weight: 600; color: #666;">1.</span>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>Branco</option>
                                                <option>Preto</option>
                                                <option>Rosa</option>
                                            </select>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>P</option>
                                                <option>M</option>
                                                <option>G</option>
                                            </select>
                                        </div>
                                        <div style="display: flex; gap: 12px; align-items: center;">
                                            <span style="font-size: 10px; width: 20px; font-weight: 600; color: #666;">2.</span>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>Branco</option>
                                                <option>Preto</option>
                                                <option>Rosa</option>
                                            </select>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>P</option>
                                                <option>M</option>
                                                <option>G</option>
                                            </select>
                                        </div>
                                        <div style="display: flex; gap: 12px; align-items: center;">
                                            <span style="font-size: 10px; width: 20px; font-weight: 600; color: #666;">3.</span>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>Branco</option>
                                                <option>Preto</option>
                                                <option>Rosa</option>
                                            </select>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>P</option>
                                                <option>M</option>
                                                <option>G</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wc-upsell-kit-option" style="position: relative; border: 2px solid; border-radius: 16px; padding: 0; cursor: pointer;">
                            <div>
                                <div style="padding: 20px 25px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="radio" style="width: 16px; height: 16px; pointer-events: none;" />
                                            <span style="font-size: 18px; font-weight: 600;">2 Unidades</span>
                                            <span class="wc-upsell-unit-price-badge" style="padding: 3px 8px; font-size: 11px; font-weight: 600; border-radius: 4px;">
                                                R$ 79,50/un
                                            </span>
                                        </div>
                                        <div>
                                            <div style="font-size: 18px; font-weight: 700;">R$ 159,00</div>
                                            <div style="font-size: 14px; text-decoration: line-through; color: #999;">R$ 179,00</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="wc-upsell-variations-container" style="display: none; padding: 20px 25px; background: #fafafa; border-top: 1px solid #e0e0e0; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                                    <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e0e0e0;">
                                        <span style="font-size: 10px; width: 20px;"></span>
                                        <span style="flex: 1; font-size: 11px; font-weight: 700; color: #333; text-transform: uppercase;">COR</span>
                                        <span style="flex: 1; font-size: 11px; font-weight: 700; color: #333; text-transform: uppercase;">TAMANHO</span>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <div style="display: flex; gap: 12px; align-items: center;">
                                            <span style="font-size: 10px; width: 20px; font-weight: 600; color: #666;">1.</span>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>Branco</option>
                                                <option>Preto</option>
                                                <option>Rosa</option>
                                            </select>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>P</option>
                                                <option>M</option>
                                                <option>G</option>
                                            </select>
                                        </div>
                                        <div style="display: flex; gap: 12px; align-items: center;">
                                            <span style="font-size: 10px; width: 20px; font-weight: 600; color: #666;">2.</span>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>Branco</option>
                                                <option>Preto</option>
                                                <option>Rosa</option>
                                            </select>
                                            <select style="flex: 1; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 12px; height: 28px; padding: 0 8px;">
                                                <option>P</option>
                                                <option>M</option>
                                                <option>G</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
