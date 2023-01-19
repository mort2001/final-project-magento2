/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/form',
    'ko',
    'uiLayout',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'mage/translate'
], function (
    $,
    _,
    Component,
    ko,
    layout,
    modal,
    url,
    $t
) {
    'use strict';

    var popUp = null;

    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/sales/order/create/shipping-address',
            modules: {
                shippingPostcode: '${ $.name }.shipping-address-edit-fieldset.postcode',
                shippingSubdistrict: '${ $.name }.shipping-address-edit-fieldset.subdistrict_id'
            }
        },

        initialize: function () {
            this._super();
            $('#shipping-custom-address-edit-form').insertAfter('#order-shipping_address_fields .field-street');
            return this;
        },

        saveAddresses: function () {
            var self = this;

            if (!this.validateAddressForm()) {
                return false;
            }

            var dataPost = {
                form_key: window.FORM_KEY,
                'billing': _.extend(self.source.shippingAddress, {
                    country_id: 'TH'
                })
            };

            $.ajax({
                type: 'POST',
                url: window.updateAddressesDataUrl,
                data: dataPost,
                dataType: 'json',

                /** @inheritdoc */
                beforeSend: function () {
                    $('body').trigger('processStart');
                },

                complete: function () {
                    $('body').trigger('processStop');
                },

                success: function (response) {
                    var errorDialog;

                    if (response.success) {
                        if (popUp) {
                            popUp.closeModal();
                        }

                        if (response.backUrl) {
                            $('#container-orders-mnp').html(response.html);
                            if (response.message) {
                                $('#messages').html(response.message);
                            }
                        }
                    } else {
                        errorDialog = $('<div class="ui-dialog-content ui-widget-content"></div>').modal({
                            type: 'popup',
                            modalClass: 'update-order-addresses-error',
                            title: $.mage.__('Error'),

                            /** @inheritdoc */
                            closed: function (e, modal) {
                                modal.modal.remove();
                            }
                        });
                        errorDialog.modal('openModal').append(response.message);
                    }
                },

                error: function (response) {
                    var errorDialog = $('<div class="ui-dialog-content ui-widget-content"></div>').modal({
                        type: 'popup',
                        modalClass: 'update-order-addresses-error',
                        title: $.mage.__('Error'),

                        /** @inheritdoc */
                        closed: function (e, modal) {
                            modal.modal.remove();
                        }
                    });
                    errorDialog.modal('openModal').
                        append($t('Something went wrong when updating order addresses data.'));
                }
            });
        },

        /**
         * @return {Boolean}
         */
        validateAddressForm: function () {
            this.source.set('params.invalid', false);

            this.source.trigger('shippingAddress.data.validate');
            if (this.source.get('shippingAddress.custom_attributes')) {
                this.source.trigger('shippingAddress.custom_attributes.data.validate');
            }

            if (!this.source.get('params.invalid')) {
                return true;
            }

            this.focusInvalid();

            return false;
        }
    });
});
