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
            console.log('WC Upsell Admin: Binding events...');
            
            // Add new kit
            $(document).on('click', '#add-new-kit', function(e) {
                console.log('WC Upsell Admin: Add new kit button clicked');
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
            console.log('WC Upsell Admin: addKit() called');
            var template = $('#wc-upsell-kit-template').html();
            
            console.log('WC Upsell Admin: Template found:', template ? 'yes' : 'no');
            console.log('WC Upsell Admin: Template length:', template ? template.length : 0);
            
            if (!template) {
                alert('Erro: Template do kit não foi encontrado. Verifique o código.');
                console.error('WC Upsell Admin: Template element exists?', $('#wc-upsell-kit-template').length);
                return;
            }
            
            template = template.replace(/\{\{INDEX\}\}/g, this.kitIndex);
            
            $('#wc-upsell-kits-list').append(template);
            this.kitIndex++;
            
            console.log('WC Upsell Admin: Kit added successfully. New index:', this.kitIndex);
            
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

                // Update display
                $row.find('.unit-price-display').html(this.formatPrice(unitPrice));
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
            var $container = $('.wc-upsell-meta-box').length ? $('.wc-upsell-meta-box') : $('#wc_upsell_product_data');
            $container.prepend($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Check for both meta box and product data panel
        if ($('.wc-upsell-meta-box').length || $('#wc_upsell_product_data').length) {
            console.log('WC Upsell Admin: Initializing...');
            WCUpsellAdmin.init();
        } else {
            console.log('WC Upsell Admin: Container not found');
        }
    });

})(jQuery);
