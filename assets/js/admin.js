/**
 * WC Upsell - Admin JavaScript
 * 
 * @package WC_Upsell
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var WCUpsellAdmin = {
        
        kitIndex: 0,

        /**
         * Initialize
         */
        init: function() {
            this.setKitIndex();
            this.bindEvents();
            this.initCalculations();
        },

        /**
         * Set initial kit index
         */
        setKitIndex: function() {
            var $rows = $('#wc-upsell-kits-list tr');
            this.kitIndex = $rows.length;
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Add new kit
            $(document).on('click', '#add-new-kit', function(e) {
                e.preventDefault();
                WCUpsellAdmin.addKit();
            });

            // Remove kit
            $(document).on('click', '.remove-kit', function(e) {
                e.preventDefault();
                WCUpsellAdmin.removeKit($(this));
            });

            // Update calculations on input change
            $(document).on('input', '.kit-quantity, .kit-price', function() {
                WCUpsellAdmin.updateKitCalculations($(this).closest('tr'));
            });

            // Make table sortable
            if ($.fn.sortable) {
                $('#wc-upsell-kits-list').sortable({
                    handle: '.sort-handle',
                    placeholder: 'ui-state-highlight',
                    update: function() {
                        WCUpsellAdmin.updateKitIndexes();
                    }
                });
            }
        },

        /**
         * Add new kit
         */
        addKit: function() {
            var template = $('#wc-upsell-kit-template').html();
            template = template.replace(/\{\{INDEX\}\}/g, this.kitIndex);
            
            $('#wc-upsell-kits-list').append(template);
            this.kitIndex++;
            
            // Update calculations for new row
            var $newRow = $('#wc-upsell-kits-list tr:last');
            this.updateKitCalculations($newRow);
        },

        /**
         * Remove kit
         */
        removeKit: function($button) {
            if (confirm('Tem certeza que deseja remover este kit?')) {
                $button.closest('tr').fadeOut(function() {
                    $(this).remove();
                    WCUpsellAdmin.updateKitIndexes();
                });
            }
        },

        /**
         * Update kit indexes after sorting or removing
         */
        updateKitIndexes: function() {
            $('#wc-upsell-kits-list tr').each(function(index) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        },

        /**
         * Initialize calculations for all kits
         */
        initCalculations: function() {
            $('#wc-upsell-kits-list tr').each(function() {
                WCUpsellAdmin.updateKitCalculations($(this));
            });
        },

        /**
         * Update kit calculations
         */
        updateKitCalculations: function($row) {
            var quantity = parseFloat($row.find('.kit-quantity').val()) || 0;
            var kitPrice = parseFloat($row.find('.kit-price').val()) || 0;
            
            // Get regular price from product (if available)
            var regularPriceText = $('.wc-upsell-product-price').text();
            var regularPriceMatch = regularPriceText.match(/[\d.,]+/);
            var regularPrice = regularPriceMatch ? parseFloat(regularPriceMatch[0].replace(',', '.')) : 0;

            if (quantity > 0) {
                // Calculate unit price
                var unitPrice = kitPrice / quantity;
                
                // Calculate discount
                var normalTotal = regularPrice * quantity;
                var discount = 0;
                
                if (normalTotal > 0 && kitPrice < normalTotal) {
                    discount = ((normalTotal - kitPrice) / normalTotal) * 100;
                }

                // Update displays
                $row.find('.unit-price-display').html(this.formatPrice(unitPrice));
                $row.find('.discount-display').text(discount.toFixed(1) + '%');
            }
        },

        /**
         * Format price
         */
        formatPrice: function(price) {
            return 'R$ ' + price.toFixed(2).replace('.', ',');
        },

        /**
         * Save kit via AJAX
         */
        saveKit: function(productId, kitData) {
            $.ajax({
                url: wcUpsellAdminParams.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_upsell_save_kit',
                    nonce: wcUpsellAdminParams.nonce,
                    product_id: productId,
                    kit_data: kitData
                },
                success: function(response) {
                    if (response.success) {
                        WCUpsellAdmin.showMessage(response.data.message, 'success');
                    } else {
                        WCUpsellAdmin.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    WCUpsellAdmin.showMessage('Erro ao salvar kit.', 'error');
                }
            });
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            var $message = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wc-upsell-meta-box').prepend($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.wc-upsell-meta-box').length) {
            WCUpsellAdmin.init();
        }
    });

})(jQuery);
