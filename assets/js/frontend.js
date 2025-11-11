/**
 * WC Upsell - Frontend JavaScript
 * 
 * @package WC_Upsell
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var WCUpsell = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.updateQuantity();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Kit selection
            $(document).on('click', '.wc-upsell-kit-option', function(e) {
                if (!$(e.target).is('input[type="radio"]')) {
                    $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
                }
            });

            // Radio change
            $(document).on('change', 'input[name="wc_upsell_selected_kit"]', function() {
                WCUpsell.selectKit($(this));
            });

            // Update quantity field when kit is selected
            $(document).on('change', 'input[name="wc_upsell_selected_kit"]', function() {
                WCUpsell.updateQuantity();
            });
        },

        /**
         * Select kit
         */
        selectKit: function($radio) {
            var $container = $radio.closest('.wc-upsell-kit-selector');
            var $option = $radio.closest('.wc-upsell-kit-option');
            var quantity = $option.data('quantity');

            // Remove selected class from all options
            $container.find('.wc-upsell-kit-option').removeClass('selected');

            // Add selected class to current option
            $option.addClass('selected');

            // Update hidden quantity field
            $('#wc-upsell-selected-quantity').val(quantity);

            // Update WooCommerce quantity field
            $('.quantity input.qty').val(quantity);

            console.log('Kit selected:', quantity);
        },

        /**
         * Update quantity field
         */
        updateQuantity: function() {
            var selectedQuantity = $('input[name="wc_upsell_selected_kit"]:checked').closest('.wc-upsell-kit-option').data('quantity');
            
            if (selectedQuantity) {
                $('.quantity input.qty').val(selectedQuantity);
            }
        },

        /**
         * Add kit to cart via AJAX
         */
        addToCart: function(productId, quantity, variationId) {
            var data = {
                action: 'wc_upsell_add_to_cart',
                nonce: wcUpsellParams.nonce,
                product_id: productId,
                quantity: quantity,
                variation_id: variationId || 0
            };

            $.ajax({
                url: wcUpsellParams.ajax_url,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    $('.wc-upsell-kit-selector').addClass('loading');
                },
                success: function(response) {
                    $('.wc-upsell-kit-selector').removeClass('loading');
                    
                    if (response.success) {
                        // Trigger WooCommerce added to cart event
                        $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash]);
                        
                        // Show success message
                        WCUpsell.showMessage(response.data.message, 'success');
                    } else {
                        WCUpsell.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    $('.wc-upsell-kit-selector').removeClass('loading');
                    WCUpsell.showMessage(wcUpsellParams.i18n.error || 'Erro ao adicionar ao carrinho', 'error');
                }
            });
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            var $message = $('<div class="woocommerce-message ' + type + '">' + message + '</div>');
            $('.wc-upsell-kit-selector').before($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WCUpsell.init();
    });

})(jQuery);
