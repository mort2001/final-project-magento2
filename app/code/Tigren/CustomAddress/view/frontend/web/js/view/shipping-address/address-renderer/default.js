/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Ui/js/modal/alert',
    'Tigren_CustomAddress/js/action/delete-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data',
    'mage/url',
    'mage/translate',
    'Tigren_CustomAddress/js/model/tmp-checkout-data'
], function (
    $,
    ko,
    Component,
    alert,
    deleteShippingAddress,
    selectShippingAddressAction,
    quote,
    formPopUpState,
    checkoutData,
    customerData,
    url,
    $t,
    tmpCheckoutData
) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/shipping-address/address-renderer/default',
            modules: {
                shipping: 'checkout.steps.shipping-step.shippingAddress'
            }
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super();

            this.isSelected = ko.computed(function () {
                var isSelected = false,
                    shippingAddress = quote.shippingAddress();

                if (shippingAddress) {
                    isSelected = shippingAddress.getKey() === this.address().getKey(); //eslint-disable-line eqeqeq
                }

                return isSelected;
            }, this);

            this.isDefault = ko.computed(function () {
                var isDefault = false,
                    address = this.address();
                if (address) {
                    isDefault = address.isDefaultShipping();
                }
                return isDefault;
            }, this);

            this.isDefaultBilling = ko.computed(function () {
                var isDefault = false,
                    address = this.address();
                if (address) {
                    isDefault = address.isDefaultBilling();
                }
                return isDefault;
            }, this);

            return this;
        },

        /**
         * @param {String} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] !== undefined ? countryData()[countryId].name : ''; //eslint-disable-line
        },

        /** Set selected customer shipping address  */
        selectAddress: function () {
            if (this.isSelected()) {
                return false;
            }
            selectShippingAddressAction(this.address());
            checkoutData.setSelectedShippingAddress(this.address().getKey());
        },

        /**
         * Edit address.
         */
        editAddress: function () {
            tmpCheckoutData.clickNewAddress(false);
            formPopUpState.isVisible(true);
            formPopUpState.hasUpdatedAddress(this.address());
            formPopUpState.isNewAddress(false);
            if (window.mobileObj) {
                window.mobileObj.defaultMobile(this.address().telephone);
            }
        },

        /**
         * Delete address.
         */
        deleteAddress: function () {
            var address = this.address();
            if (address.customerAddressId) {
                if (address.isDefaultShipping()) {
                    alert({
                        title: '',
                        content: $t('Can\'t delete default shipping address')
                    });
                } else {

                    $.ajax({
                        url: url.build('custom_address/customer/deleteAddress'),
                        method: 'post',
                        data: { id: address.customerAddressId },
                        datatype: 'json',
                        beforeSend: function () {
                            $('body').trigger('processStart');
                        },
                        complete: function () {
                            $('body').trigger('processStop');
                        },
                        success: function (response) {
                            if (response.success) {
                                deleteShippingAddress(address);
                            } else {
                                // this.errorValidationMessage($t('You cannot delete this address for now.'));
                            }
                        }
                    });
                }
            } else {
                deleteShippingAddress(address);
                checkoutData.setNewCustomerShippingAddress({});
                $('.action-show-popup').show();
            }
        }
    });
});
