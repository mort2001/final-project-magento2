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
    'Tigren_CustomAddress/js/action/delete-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/model/quote',
    'Tigren_CustomAddress/js/model/billing-address/form-popup-state',
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
    deleteBillingAddress,
    selectBillingAddressAction,
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
            template: 'Tigren_CustomAddress/billing-address/address-renderer/default'
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super();

            this.isSelected = ko.computed(function () {
                var isSelected = false,
                    billingAddress = quote.billingAddress();

                if (billingAddress) {
                    isSelected = billingAddress.getKey() === this.address().getKey(); //eslint-disable-line eqeqeq
                }

                return isSelected;
            }, this);

            this.isDefault = ko.computed(function () {
                var isDefault = false,
                    address = this.address();
                if (address) {
                    isDefault = address.isDefaultBilling();
                }
                return isDefault;
            }, this);

            this.isDefaultShipping = ko.computed(function () {
                var isDefault = false,
                    address = this.address();
                if (address) {
                    isDefault = address.isDefaultShipping();
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
            return countryData()[countryId] != undefined ? countryData()[countryId].name : ''; //eslint-disable-line
        },

        /** Set selected customer billing address  */
        selectAddress: function () {
            if (this.isSelected()) {
                return false;
            }
            selectBillingAddressAction(this.address());
            checkoutData.setSelectedBillingAddress(this.address().getKey());
        },

        /**
         * Edit address.
         */
        editAddress: function (parent) {
            tmpCheckoutData.clickNewAddress(false);
            formPopUpState.isVisible(parent.paymentMethodCode);
            formPopUpState.hasUpdatedAddress(this.address());
            formPopUpState.isNewAddress(false);
            if (window.mobileObj) {
                window.mobileObj.defaultMobile(this.address().telephone);
                $('div[name="billingAddress.telephone"] input[name="telephone"]').val(this.address().telephone);
            }
        },

        deleteAddress: function () {
            var address = this.address();
            if (address.customerAddressId) {
                if (address.isDefaultBilling()) {
                    alert({
                        title: '',
                        content: $t('Can\'t delete default billing address')
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
                                deleteBillingAddress(address);
                            } else {
                                // this.errorValidationMessage($t('You cannot delete this address for now.'));
                            }
                        }
                    });
                }
            } else {
                deleteBillingAddress(address);
                checkoutData.setNewCustomerBillingAddress({});
                $('.action-show-popup').show();
            }
        }
    });
});
