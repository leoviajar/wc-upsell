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
            // Only initialize if kit selector exists
            if ($('.wc-upsell-kit-selector').length === 0) {
                return;
            }
            
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
                var $form = $(this);
                
                // Check if this is a kit with variations
                if ($('input[name="wc_upsell_kit"]').length && WCUpsell.isVariable) {
                    e.preventDefault();
                    
                    // Validate variations
                    if (!WCUpsell.validateVariations()) {
                        WCUpsell.showMessage('Por favor, selecione todas as variações do kit.', 'error');
                        return false;
                    }
                    
                    // Add each variation to cart separately
                    WCUpsell.addKitToCart();
                    
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

            // Check if already selected
            if ($option.hasClass('selected')) {
                return; // Do nothing if already selected
            }

            // Remove selected class from all options
            $container.find('.wc-upsell-kit-option').removeClass('selected');

            // Add selected class to current option
            $option.addClass('selected');

            // Hide all variation containers
            $container.find('.wc-upsell-variations-container').hide();

            // Show variations for selected kit (no animation)
            var $variations = $option.find('.wc-upsell-variations-container');
            $variations.show();

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
         * Add kit to cart (each variation separately)
         */
        addKitToCart: function() {
            var $selectedKit = $('.wc-upsell-kit-option.selected');
            var $container = $selectedKit.find('.wc-upsell-variations-container');
            var productId = $('.wc-upsell-kit-selector').data('product-id');
            var quantity = $selectedKit.data('quantity');
            
            // Get kit data
            var kitData = {
                product_id: productId,
                quantity: quantity,
                variations: []
            };
            
            // Collect each variation's attributes
            $container.find('.wc-upsell-variation-item').each(function() {
                var attributes = {};
                
                $(this).find('.wc-upsell-variation-select').each(function() {
                    var $select = $(this);
                    var attrName = $select.data('attribute');
                    var attrValue = $select.val();
                    
                    if (attrName && attrValue) {
                        attributes['attribute_' + attrName] = attrValue;
                    }
                });
                
                // Find variation ID
                var variationId = WCUpsell.findVariationId(attributes);
                
                if (variationId) {
                    kitData.variations.push({
                        variation_id: variationId,
                        attributes: attributes
                    });
                }
            });
            
            // Add to cart via AJAX
            WCUpsell.ajaxAddKitToCart(kitData);
        },

        /**
         * Find variation ID from attributes
         */
        findVariationId: function(attributes) {
            if (!this.variationsData) {
                return 0;
            }
            
            for (var i = 0; i < this.variationsData.length; i++) {
                var variation = this.variationsData[i];
                var match = true;
                
                for (var attrKey in attributes) {
                    var variationValue = variation.attributes[attrKey];
                    var selectedValue = attributes[attrKey];
                    
                    // Normalize comparison (case insensitive, trim)
                    if (variationValue && selectedValue) {
                        if (variationValue.toLowerCase().trim() !== selectedValue.toLowerCase().trim()) {
                            match = false;
                            break;
                        }
                    } else if (variationValue !== selectedValue) {
                        match = false;
                        break;
                    }
                }
                
                if (match) {
                    return variation.variation_id;
                }
            }
            
            return 0;
        },

        /**
         * Add kit to cart via AJAX
         */
        ajaxAddKitToCart: function(kitData) {
            $.ajax({
                url: wcUpsellParams.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_upsell_add_kit',
                    security: wcUpsellParams.nonce,
                    kit_data: JSON.stringify(kitData)
                },
                beforeSend: function() {
                    $('form.cart').addClass('loading');
                },
                success: function(response) {
                    $('form.cart').removeClass('loading');
                    
                    if (response.success) {
                        // Always redirect to cart page
                        var cartUrl = response.data.cart_url || (typeof wc_add_to_cart_params !== 'undefined' ? wc_add_to_cart_params.cart_url : '/carrinho');
                        window.location.href = cartUrl;
                    } else {
                        WCUpsell.showMessage(response.data || 'Erro ao adicionar ao carrinho', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $('form.cart').removeClass('loading');
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    WCUpsell.showMessage('Erro ao adicionar ao carrinho', 'error');
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
