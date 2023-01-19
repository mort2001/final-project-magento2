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
            template: 'Tigren_CustomAddress/customer/address/edit-form',
            modules: {
                shippingPostcode: '${ $.name }.shipping-address-edit-fieldset.postcode',
                shippingSubdistrict: '${ $.name }.shipping-address-edit-fieldset.subdistrict_id',
                billingPostcode: '${ $.name }.billing-address-edit-fieldset.postcode',
                billingSubdistrict: '${ $.name }.billing-address-edit-fieldset.subdistrict_id'
            }
        },

        initialize: function () {
            this._super();

            $(document).on('click', 'button.update-address-action', this.openPopupForm.bind(this));

            return this;
        },

        openPopupForm: function (event) {
            var self = this,
                orderId = $(event.currentTarget).data('order-id');

            $.ajax({
                type: 'GET',
                url: window.getAddressesDataUrl,
                data: { orderId: orderId },
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
                        self.reset();

                        var shippingStreetObject;
                        if ($.isArray(response.shippingAddress.street)) {
                            shippingStreetObject = {};
                            response.shippingAddress.street.forEach(function (value, index) {
                                shippingStreetObject[index] = value;
                            });
                            response.shippingAddress.street = shippingStreetObject;
                        }
                        _.each(response.shippingAddress, function (value, name) {
                            if (name === 'city_id' || name === 'subdistrict') {
                                this.source.set('shippingAddress.custom_attributes.' + name, value);
                            } else if (name === 'subdistrict_id') {
                                this.source.set('shippingAddress.custom_attributes.' + name, value);
                                if (this.shippingSubdistrict()) {
                                    this.shippingSubdistrict().value(value);
                                }
                            } else if (name !== 'postcode') {
                                this.source.set('shippingAddress.' + name, value);
                            }
                        }, self);
                        if (self.shippingPostcode()) {
                            self.shippingPostcode().initAddressDataWithPostcode(false);
                            self.shippingPostcode().filterInputValue('');
                            setTimeout(function () {
                                self.shippingPostcode().value(response.shippingAddress.postcode);
                            }, 600);
                        }

                        var billingStreetObject;
                        if ($.isArray(response.billingAddress.street)) {
                            billingStreetObject = {};
                            response.billingAddress.street.forEach(function (value, index) {
                                billingStreetObject[index] = value;
                            });
                            response.billingAddress.street = billingStreetObject;
                        }
                        _.each(response.billingAddress, function (value, name) {
                            if (name === 'city_id' || name === 'subdistrict') {
                                this.source.set('billingAddress.custom_attributes.' + name, value);
                            } else if (name === 'subdistrict_id') {
                                this.source.set('billingAddress.custom_attributes.' + name, value);
                                if (this.billingSubdistrict()) {
                                    this.billingSubdistrict().value(value);
                                }
                            } else if (name !== 'postcode') {
                                this.source.set('billingAddress.' + name, value);
                            }
                        }, self);
                        if (self.billingPostcode()) {
                            self.billingPostcode().initAddressDataWithPostcode(false);
                            self.billingPostcode().filterInputValue('');
                            setTimeout(function () {
                                self.billingPostcode().value(response.billingAddress.postcode);
                            }, 600);
                        }

                        setTimeout(function () {
                            var popupForm = self.getPopUp.bind(self);
                            popupForm().openModal();
                        }, 600);
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
                        append($t('Something went wrong when getting order addresses data.'));
                }
            });
        },

        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [
                    {
                        text: buttons.save.text ? buttons.save.text : $t('Update Addresses'),
                        class: buttons.save.class ? buttons.save.class : 'action primary action-update-address',
                        click: self.saveAddresses.bind(self)
                    }
                ];

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this)
                };

                /** @inheritdoc */
                this.popUpForm.options.opened = function () {
                    // do something here
                };
            }

            if (popUp) {
                popUp.closeModal();
                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            } else {
                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            }

            return popUp;
        },

        /**
         * Close modal.
         */
        onClosePopUp: function () {
            this.getPopUp().closeModal();
        },

        saveAddresses: function () {
            var self = this;

            if (!this.validateAddressForm()) {
                return false;
            }

            var dataPost = {
                form_key: window.FORM_KEY,
                'shipping': _.extend(self.source.shippingAddress, {
                    country_id: 'TH'
                }),
                'billing': _.extend(self.source.billingAddress, {
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

            this.source.trigger('billingAddress.data.validate');
            if (this.source.get('billingAddress.custom_attributes')) {
                this.source.trigger('billingAddress.custom_attributes.data.validate');
            }

            if (!this.source.get('params.invalid')) {
                return true;
            }

            this.focusInvalid();

            return false;
        }
    });
});
