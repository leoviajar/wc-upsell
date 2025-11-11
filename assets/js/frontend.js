/**
 * WC Upsell - Frontend JavaScript
 * 
 * @package WC_Upsell
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var WCUpsell = {
        
        variationsData: null,
        isVariable: false,

        /**
         * Initialize
         */
        init: function() {
            this.loadVariationsData();
            this.bindEvents();
            this.updateQuantity();
        },

        /**
         * Load variations data
         */
        loadVariationsData: function() {
            var $selector = $('.wc-upsell-kit-selector');
            this.isVariable = $selector.data('is-variable') === 1;
            
            if (this.isVariable) {
                var $dataElement = $('#wc-upsell-variations-data');
                if ($dataElement.length) {
                    try {
                        this.variationsData = JSON.parse($dataElement.text());
                    } catch(e) {
                        console.error('Error parsing variations data:', e);
                    }
                }
            }
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Kit selection
            $(document).on('click', '.wc-upsell-kit-option', function(e) {
                if (!$(e.target).is('input[type="radio"]') && !$(e.target).is('select')) {
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

            // Prevent form submission if variations not selected
            $('form.cart').on('submit', function(e) {
                if (!WCUpsell.validateVariations()) {
                    e.preventDefault();
                    WCUpsell.showMessage('Por favor, selecione todas as variações do kit.', 'error');
                    return false;
                }
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

            // Hide all variation containers
            $container.find('.wc-upsell-variations-container').hide();

            // Show variations for selected kit
            $option.find('.wc-upsell-variations-container').slideDown(300);

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
         * Validate variations selection
         */
        validateVariations: function() {
            if (!this.isVariable) {
                return true;
            }

            var $selectedKit = $('.wc-upsell-kit-option.selected');
            if (!$selectedKit.length) {
                return true;
            }

            var $variationSelects = $selectedKit.find('.wc-upsell-variation-select');
            var allSelected = true;

            $variationSelects.each(function() {
                if (!$(this).val()) {
                    allSelected = false;
                    $(this).css('border-color', '#dc3232');
                } else {
                    $(this).css('border-color', '');
                }
            });

            return allSelected;
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
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 300);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WCUpsell.init();
    });

})(jQuery);
