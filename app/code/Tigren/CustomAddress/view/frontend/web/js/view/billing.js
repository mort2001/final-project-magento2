/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'ko',
    'underscore',
    'matchMedia',
    'mage/utils/objects',
    'Magento_Ui/js/form/form',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/customer/address',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Tigren_CustomAddress/js/model/billing-address/form-popup-state',
    'Tigren_CustomAddress/js/action/edit-billing-address',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/action/set-billing-address',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'mage/url',
    'Tigren_CustomAddress/js/model/tmp-checkout-data'
], function (
    $,
    ko,
    _,
    mediaCheck,
    mageUtils,
    Component,
    customer,
    address,
    addressList,
    addressConverter,
    quote,
    formPopUpState,
    editBillingAddress,
    createBillingAddress,
    selectBillingAddress,
    checkoutData,
    checkoutDataResolver,
    customerData,
    setBillingAddressAction,
    modal,
    globalMessageList,
    $t,
    url,
    tmpCheckoutData
) {
    'use strict';

    var lastSelectedBillingAddress = null,
        addressUpdated = false,
        addressEdited = false,
        newAddressOption = {
            /**
             * Get new address label
             * @returns {String}
             */
            getAddressInline: function () {
                return $t('New Address');
            },
            customerAddressId: null
        },
        countryData = customerData.get('directory-data'),
        addressOptions = addressList().filter(function (address) {
            return address.getType() === 'customer-address'; //eslint-disable-line eqeqeq
        });

    addressOptions.push(newAddressOption);

    var popUp = null;

    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/billing',
            billingFormTemplate: 'Tigren_CustomAddress/billing-address/form',
            actionsTemplate: 'Tigren_CustomAddress/billing-address/actions',
            detailsTemplate: 'Tigren_CustomAddress/billing-address/details',
            detailsAsGuestTemplate: 'Tigren_CustomAddress/billing-address/detailsAsGuest',
            billingFormAsGuestTemplate: 'Tigren_CustomAddress/billing-address/formAsGuest',
            modules: {
                postcode: '${ $.name }.address-fieldset.postcode',
                subdistrict: '${ $.name }.address-fieldset.subdistrict_id'
            }
        },
        currentBillingAddress: quote.billingAddress,
        addressOptions: addressOptions,
        customerHasAddresses: addressOptions.length > 1,
        visible: ko.observable(!quote.isVirtual()),
        errorValidationMessage: ko.observable(false),
        isCustomerLoggedIn: customer.isLoggedIn,
        isFormPopUpVisible: formPopUpState.isVisible,
        isFormInline: addressList().length === 0,
        isNewAddressAdded: ko.observable(false),
        hasUpdatedAddress: formPopUpState.hasUpdatedAddress,

        /**
         * Init component
         */
        initialize: function () {
            this._super();
            quote.paymentMethod.subscribe(function () {
                checkoutDataResolver.resolveBillingAddress();
            }, this);
        },

        /**
         * @return {exports.initObservable}
         */
        initObservable: function () {
            this._super().observe({
                selectedAddress: null,
                isAddressDetailsVisible: quote.billingAddress() != null,
                isAddressFormVisible: !customer.isLoggedIn() || !addressOptions.length,
                isAddressSameAsShipping: true,
                saveInAddressBook: 1
            });

            checkoutDataResolver.resolveBillingAddress();

            if (quote.isVirtual()) {
                this.isAddressSameAsShipping(false);
            }

            var self = this,
                hasNewAddress = addressList.some(function (address) {
                    return address.getType() === 'new-customer-address' || address.getType() === 'new-billing-address'; //eslint-disable-line eqeqeq
                });

            this.isNewAddressAdded(hasNewAddress);

            this.isFormPopUpVisible.subscribe(function (value) {
                if (value && value === self.paymentMethodCode) {
                    self.getPopUp().openModal();
                }
            });

            if (this.isAddressSameAsShipping() || (!quote.isVirtual() && quote.isMoveBilling())) {
                this.isAddressDetailsVisible(true);
            } else {
                lastSelectedBillingAddress = quote.billingAddress();
                quote.billingAddress(null);
                this.isAddressDetailsVisible(false);
                if (quote.isVirtual()) {
                    this.isAddressDetailsVisible(true);
                }
            }

            return this;
        },

        canUseShippingAddress: ko.computed(function () {
            return !quote.isVirtual() && quote.shippingAddress() && quote.shippingAddress().canUseForBilling();
        }),

        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [
                    {
                        text: buttons.save.text ? buttons.save.text : $t('Save'),
                        class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                        click: self.saveNewAddress.bind(self)
                    }
                ];

                /** @inheritdoc */
                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this)
                };

                /** @inheritdoc */
                this.popUpForm.options.opened = function () {
                    // Store temporary address for revert action in case when user click cancel action
                    var addressData,
                        isNewAddressPopup = false;

                    if (self.hasUpdatedAddress()) {
                        addressData = addressConverter.quoteAddressToFormAddressData(
                            self.hasUpdatedAddress()
                        );
                        checkoutData.setBillingAddressFromData($.extend(true, {}, addressData));
                        self.hasUpdatedAddress(false);
                    } else {
                        isNewAddressPopup = true;
                    }

                    self.temporaryAddress = $.extend(true, {}, checkoutData.getBillingAddressFromData());

                    if (isNewAddressPopup) {
                        self.popUpForm.options.title = $t('New Address');
                        $.each(self.elems(), function (index, elem) {
                            if (elem.reset !== undefined) {
                                elem.reset();
                            }
                        });
                    } else {
                        console.log(this.dataScopePrefix);
                        self.popUpForm.options.title = $t('Edit Address');
                        _.each(self.temporaryAddress, function (value, name) {
                            if (name === 'custom_attributes') {
                                _.each(value, function (customAttributeValue, customAttributeName) {
                                    if (customAttributeName === 'subdistrict_id') {
                                        this.source.set(
                                            this.dataScopePrefix + '.custom_attributes.' + customAttributeName,
                                            customAttributeValue);
                                        if (this.subdistrict()) {
                                            this.subdistrict().value(customAttributeValue);
                                        }
                                    } else {
                                        this.source.set(
                                            this.dataScopePrefix + '.custom_attributes.' + customAttributeName,
                                            customAttributeValue);
                                    }
                                }, this);
                            } else if (name === 'type') {
                                this.source.set(this.dataScopePrefix + '.' + name, 'new-billing-address');
                            } else if (name !== 'postcode') {
                                this.source.set(this.dataScopePrefix + '.' + name, value);
                            }
                        }, self);
                        if (self.postcode()) {
                            if (!quote.useDropdown()) {
                                self.postcode().initAddressDataWithPostcode(false);
                                setTimeout(function () {
                                    self.postcode().value(self.temporaryAddress.postcode);
                                }, 600);
                            } else {
                                setTimeout(function () {
                                    self.postcode().value(self.temporaryAddress.postcode);
                                }, 600);
                            }
                        }
                    }
                };
            }

            if (tmpCheckoutData.clickNewAddress()) {
                this.popUpForm.options.title = $t('New Address');
            } else {
                this.popUpForm.options.title = $t('Edit Address');
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
         * Update address action
         */
        updateAddress: function () {
            var addressData, newBillingAddress;
            addressUpdated = true;
            if (this.selectedAddress() && this.selectedAddress() !== newAddressOption) { //eslint-disable-line eqeqeq
                selectBillingAddress(this.selectedAddress());
                checkoutData.setSelectedBillingAddress(this.selectedAddress().getKey());
            } else {
                this.source.set('params.invalid', false);
                this.source.trigger(this.dataScopePrefix + '.data.validate');

                if (this.source.get(this.dataScopePrefix + '.custom_attributes')) {
                    this.source.trigger(this.dataScopePrefix + '.custom_attributes.data.validate');
                }

                if (!this.source.get('params.invalid')) {
                    addressData = this.source.get(this.dataScopePrefix);

                    if (customer.isLoggedIn() && !this.customerHasAddresses) { //eslint-disable-line max-depth
                        this.saveInAddressBook(1);
                    }
                    addressData['save_in_address_book'] = this.saveInAddressBook() ? 1 : 0;
                    newBillingAddress = createBillingAddress(addressData);

                    // New address must be selected as a billing address
                    selectBillingAddress(newBillingAddress);
                    checkoutData.setSelectedBillingAddress(newBillingAddress.getKey());
                    checkoutData.setNewCustomerBillingAddress(addressData);
                }
            }
            setBillingAddressAction(globalMessageList);
            this.updateAddresses();
        },

        /**
         * Save new billing address
         */
        saveNewAddress: function () {
            var self = this,
                addressFormData,
                customerAddressId,
                newBillingAddress,
                updatedBillingAddress;

            this.source.set('params.invalid', false);
            this.triggerBillingDataValidateEvent();

            if (!this.source.get('params.invalid')) {
                addressFormData = this.source.get(this.dataScopePrefix);

                var customAttributes = addressFormData['custom_attributes'];
                $.each(customAttributes, function (attributeCode, value) {
                    if ($.isNumeric(attributeCode))
                        delete customAttributes[attributeCode];
                });
                addressFormData['custom_attributes'] = customAttributes;
                if (formPopUpState.isNewAddress())
                    addressFormData['customer_address_id'] = null;
                this.source.set(this.dataScopePrefix, addressFormData);

                customerAddressId = addressFormData.customer_address_id;

                if (customerAddressId) {
                    $.ajax({
                        url: url.build('custom_address/customer/updateAddress'),
                        method: 'post',
                        data: addressFormData,
                        datatype: 'json',
                        beforeSend: function () {
                            $('body').trigger('processStart');
                        },
                        complete: function () {
                            $('body').trigger('processStop');
                        },
                        success: function (response) {
                            if (response.success) {
                                var addressData = $.extend(true, {}, addressFormData),
                                    region,
                                    regionName = addressData.region;

                                if (mageUtils.isObject(addressData.street)) {
                                    addressData.street = self.objectToArray(addressData.street);
                                }

                                addressData.region = {
                                    'region_id': addressData['region_id'],
                                    'region_code': addressData['region_code'],
                                    region: regionName
                                };

                                if (addressData['region_id'] &&
                                    countryData()[addressData['country_id']] &&
                                    countryData()[addressData['country_id']].regions
                                ) {
                                    region = countryData()[addressData['country_id']].regions[addressData['region_id']];

                                    if (region) {
                                        addressData.region['region_id'] = addressData['region_id'];
                                        addressData.region['region_code'] = region.code;
                                        addressData.region.region = region.name;
                                    }
                                } else if (
                                    !addressData['region_id'] &&
                                    countryData()[addressData['country_id']] &&
                                    countryData()[addressData['country_id']].regions
                                ) {
                                    addressData.region['region_code'] = '';
                                    addressData.region.region = '';
                                }
                                delete addressData['region_id'];

                                updatedBillingAddress = editBillingAddress(address(addressData));
                                selectBillingAddress(updatedBillingAddress);
                                checkoutData.setSelectedBillingAddress(updatedBillingAddress.getKey());
                                self.getPopUp().closeModal();
                                self.hasUpdatedAddress(false);
                            }
                        }
                    });
                } else {
                    // if user clicked the checkbox, its value is true or false. Need to convert.
                    addressFormData['save_in_address_book'] = this.saveInAddressBook() ? 1 : 0;

                    // New address must be selected as a billing address
                    newBillingAddress = createBillingAddress(addressFormData);
                    selectBillingAddress(newBillingAddress);
                    checkoutData.setSelectedBillingAddress(newBillingAddress.getKey());
                    checkoutData.setNewCustomerBillingAddress($.extend(true, {}, addressFormData));
                    this.getPopUp().closeModal();
                    this.isNewAddressAdded(true);
                }
            }
        },

        /**
         * Edit address action
         */
        editAddress: function () {
            addressUpdated = false;
            addressEdited = true;
            lastSelectedBillingAddress = quote.billingAddress();
            quote.billingAddress(null);
            this.isAddressDetailsVisible(false);
        },

        /**
         * Cancel address edit action
         */
        cancelAddressEdit: function () {
            addressUpdated = true;
            this.restoreBillingAddress();

            if (quote.billingAddress()) {
                // restore 'Same As Shipping' checkbox state
                this.isAddressSameAsShipping(
                    quote.billingAddress() != null &&
                    quote.billingAddress().getCacheKey() === quote.shippingAddress().getCacheKey() && //eslint-disable-line
                    !quote.isVirtual()
                );
                this.isAddressDetailsVisible(true);
            }
        },

        /**
         * Manage cancel button visibility
         */
        canUseCancelBillingAddress: ko.computed(function () {
            return quote.billingAddress() || lastSelectedBillingAddress;
        }),

        /**
         * Restore billing address
         */
        restoreBillingAddress: function () {
            if (lastSelectedBillingAddress != null) {
                selectBillingAddress(lastSelectedBillingAddress);
            }
        },

        /**
         * Navigator change hash handler.
         *
         * @param {Object} step - navigation step
         */
        navigate: function (step) {
            step && step.isVisible(true);
        },

        /**
         * Revert address and close modal.
         */
        onClosePopUp: function () {
            checkoutData.setBillingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.getPopUp().closeModal();
        },

        /**
         * Show address form popup
         */
        showFormPopUp: function (parent) {
            tmpCheckoutData.clickNewAddress(true);
            this.isFormPopUpVisible(this.getCode(parent));
            formPopUpState.isNewAddress(true);
            if (window.mobileObj) {
                window.mobileObj.defaultMobile('');
            }
        },

        /**
         * Trigger action to update shipping and billing addresses
         */
        updateAddresses: function () {
            if (window.checkoutConfig.reloadOnBillingAddress ||
                !window.checkoutConfig.displayBillingOnPaymentMethod
            ) {
                this.isAddressDetailsVisible(true);
                setBillingAddressAction(globalMessageList);
            }
        },

        /**
         * Trigger Billing data Validate Event.
         */
        triggerBillingDataValidateEvent: function () {
            this.source.trigger(this.dataScopePrefix + '.data.validate');

            if (this.source.get(this.dataScopePrefix + '.custom_attributes')) {
                this.source.trigger(this.dataScopePrefix + '.custom_attributes.data.validate');
            }
        },

        /**
         * @return {Boolean}
         */
        canUseBillingAddress: ko.computed(function () {
            return !quote.isVirtual() && quote.shippingAddress() && quote.shippingAddress().canUseForBilling();
        }),

        /**
         * @param {Object} address
         * @return {*}
         */
        addressOptionsText: function (address) {
            return address.getAddressInline();
        },

        /**
         * @return {Boolean}
         */
        useShippingAddress: function () {
            if (this.isAddressSameAsShipping()) {
                selectBillingAddress(quote.shippingAddress());
                this.updateAddresses();
                this.isAddressDetailsVisible(true);
                tmpCheckoutData.isBillingSameShipping(true);
            } else {
                lastSelectedBillingAddress = quote.billingAddress();
                quote.billingAddress(null);
                this.isAddressDetailsVisible(false);
                tmpCheckoutData.isBillingSameShipping(false);
            }
            checkoutData.setSelectedBillingAddress(null);
            return true;
        },

        /**
         * @param {Object} address
         */
        onAddressChange: function (address) {
            // do nothing
        },

        /**
         * @param {int} countryId
         * @return {*}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] !== undefined ? countryData()[countryId].name : '';
        },

        /**
         * Get code
         * @param {Object} parent
         * @returns {String}
         */
        getCode: function (parent) {
            return parent && _.isFunction(parent.getCode) ? parent.getCode() : 'shared';
        },

        /**
         * Convert object to array
         * @param {Object} object
         * @returns {Array}
         */
        objectToArray: function (object) {
            var convertedArray = [];

            $.each(object, function (key) {
                return typeof object[key] === 'string' ? convertedArray.push(object[key]) : false;
            });

            return convertedArray.slice(0);
        },

        customerHasAddress: function () {
            return !!(window.checkoutConfig.customerData.default_shipping);
        }
    });
});
