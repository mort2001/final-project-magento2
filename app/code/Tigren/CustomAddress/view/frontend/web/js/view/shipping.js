/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'underscore',
    'ko',
    'matchMedia',
    'mage/utils/objects',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/customer/address',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/action/create-shipping-address',
    'Tigren_CustomAddress/js/action/edit-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-rate-service',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Ui/js/model/messages',
    'uiLayout',
    'uiRegistry',
    'mage/url',
    'mage/translate',
    'Tigren_CustomAddress/js/model/tmp-checkout-data',
    'mage/validation'
], function (
    $,
    _,
    ko,
    mediaCheck,
    mageUtils,
    customerData,
    customer,
    address,
    addressList,
    addressConverter,
    quote,
    selectShippingMethodAction,
    createShippingAddress,
    editShippingAddress,
    selectShippingAddress,
    createBillingAddress,
    selectBillingAddress,
    setShippingInformationAction,
    modal,
    stepNavigator,
    checkoutData,
    shippingService,
    formPopUpState,
    Messages,
    layout,
    registry,
    url,
    $t,
    tmpCheckoutData
) {
    'use strict';

    var popUp = null,
        countryData = customerData.get('directory-data');

    return function (Component) {
        return Component.extend({
            defaults: {
                template: 'Tigren_CustomAddress/shipping',
                shippingFormTemplate: 'Tigren_CustomAddress/shipping-address/form',
                authenticationTemplate: 'Tigren_CustomAddress/authentication',
                isAddressSameAsShipping: ko.observable(true),
                isShowBillingForm: ko.observable(false),
                loginUrl: url.build('customer/account/login/'),
                modules: {
                    postcode: '${ $.name }.shipping-address-fieldset.postcode',
                    subdistrict: '${ $.name }.shipping-address-fieldset.subdistrict_id'
                }
            },

            hasUpdatedAddress: formPopUpState.hasUpdatedAddress,
            isMoveBilling: ko.observable(quote.isMoveBilling()),

            /**
             * @return {exports}
             */
            initialize: function () {
                this._super();

                registry.async('checkoutProvider')(function () {
                    var shippingAddressData = checkoutData.getShippingAddressFromData();

                    if (this.isFormInline && shippingAddressData) {
                        _.each(shippingAddressData, function (value, name) {
                            if (name === 'custom_attributes') {
                                _.each(value, function (customAttributeValue, customAttributeName) {
                                    if (customAttributeName === 'subdistrict_id') {
                                        this.source.set('shippingAddress.custom_attributes.' + customAttributeName,
                                            customAttributeValue);
                                        if (this.subdistrict()) {
                                            this.subdistrict().value(customAttributeValue);
                                        }
                                    } else {
                                        this.source.set('shippingAddress.custom_attributes.' + customAttributeName,
                                            customAttributeValue);
                                    }
                                }, this);
                            } else if (name === 'type') {
                                this.source.set('shippingAddress.' + name, 'new-shipping-address');
                            } else if (name !== 'postcode') {
                                this.source.set('shippingAddress.' + name, value);
                            }
                        }, this);
                        if (this.postcode()) {
                            if (!quote.useDropdown()) {
                                this.postcode().initAddressDataWithPostcode(false);
                                setTimeout(function () {
                                    this.postcode().value(shippingAddressData.postcode);
                                }.bind(this), 600);
                            } else {
                                setTimeout(function () {
                                    this.postcode().value(shippingAddressData.postcode);
                                }.bind(this), 600);
                            }
                        }

                        this.triggerShippingDataValidateEvent();
                    }
                }.bind(this));

                return this;
            },

            initChildren: function () {
                this.messageContainer = new Messages();
                this.createMessagesComponent();
                return this;
            },

            createMessagesComponent: function () {
                var messagesComponent = {
                    parent: this.name,
                    name: this.name + '.messages',
                    displayArea: 'messages',
                    component: 'Magento_Ui/js/view/messages',
                    config: {
                        messageContainer: this.messageContainer
                    }
                };

                layout([messagesComponent]);

                return this;
            },

            isCustomerLoggedIn: function () {
                return !customer.isLoggedIn();
            },

            login: function () {
                $.cookie('login_redirect', window.checkoutConfig.checkoutUrl);
                location.href = this.loginUrl;
            },

            /**
             * Show address form popup
             */
            showFormPopUp: function () {
                tmpCheckoutData.clickNewAddress(true);
                this.isFormPopUpVisible(true);
                formPopUpState.isNewAddress(true);
                if (window.mobileObj) {
                    window.mobileObj.defaultMobile('');
                }
            },

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
                            checkoutData.setShippingAddressFromData($.extend(true, {}, addressData));
                            self.hasUpdatedAddress(false);
                        } else {
                            isNewAddressPopup = true;
                        }

                        self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());

                        if (isNewAddressPopup) {
                            self.popUpForm.options.title = $t('New Address');
                            $.each(self.elems(), function (index, elem) {
                                if (elem.reset !== undefined) {
                                    elem.reset();
                                }
                            });
                        } else {
                            self.popUpForm.options.title = $t('Edit Address');
                            _.each(self.temporaryAddress, function (value, name) {
                                if (name === 'custom_attributes') {
                                    _.each(value, function (customAttributeValue, customAttributeName) {
                                        if (customAttributeName === 'subdistrict_id') {
                                            this.source.set('shippingAddress.custom_attributes.' + customAttributeName,
                                                customAttributeValue);
                                            if (this.subdistrict()) {
                                                this.subdistrict().value(customAttributeValue);
                                            }
                                        } else {
                                            this.source.set('shippingAddress.custom_attributes.' + customAttributeName,
                                                customAttributeValue);
                                        }
                                    }, this);
                                } else if (name === 'type') {
                                    this.source.set('shippingAddress.' + name, 'new-shipping-address');
                                } else if (name !== 'postcode') {
                                    this.source.set('shippingAddress.' + name, value);
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
             * Save new shipping address
             */
            saveNewAddress: function () {
                var self = this,
                    addressFormData,
                    customerAddressId,
                    newShippingAddress,
                    updatedShippingAddress;

                this.source.set('params.invalid', false);
                this.triggerShippingDataValidateEvent();

                if (!this.source.get('params.invalid')) {
                    addressFormData = this.source.get('shippingAddress');

                    var customAttributes = addressFormData['custom_attributes'];
                    $.each(customAttributes, function (attributeCode, value) {
                        if ($.isNumeric(attributeCode))
                            delete customAttributes[attributeCode];
                    });
                    addressFormData['custom_attributes'] = customAttributes;
                    if (formPopUpState.isNewAddress())
                        addressFormData['customer_address_id'] = null;
                    this.source.set('shippingAddress', addressFormData);

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

                                    updatedShippingAddress = editShippingAddress(address(addressData));
                                    selectShippingAddress(updatedShippingAddress);
                                    checkoutData.setSelectedShippingAddress(updatedShippingAddress.getKey());
                                    self.getPopUp().closeModal();
                                    self.hasUpdatedAddress(false);
                                }
                            }
                        });
                    } else {
                        // if user clicked the checkbox, its value is true or false. Need to convert.
                        addressFormData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;

                        // New address must be selected as a shipping address
                        newShippingAddress = createShippingAddress(addressFormData);
                        selectShippingAddress(newShippingAddress);
                        checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                        checkoutData.setNewCustomerShippingAddress($.extend(true, {}, addressFormData));
                        this.getPopUp().closeModal();
                        this.isNewAddressAdded(true);
                    }
                }
            },

            setShippingInformation: function () {
                if (
                    this.validateContactInformationForm()
                    && this.validateShippingInformation()
                    && this.validateBillingInformation()
                    && this.validateFullTaxInvoice()
                ) {
                    quote.shippingAddress($.extend(quote.shippingAddress(), this.source.contactInformation));
                    setShippingInformationAction().done(function () {
                        stepNavigator.next();
                    });
                }
            },

            validateContactInformationForm: function () {
                this.source.set('params.invalid', false);
                this.source.trigger('contactInformation.data.validate');

                if (!this.source.get('params.invalid')) {
                    return true;
                }

                this.focusInvalid();

                return false;
            },

            validateBillingInformation: function () {
                if (!quote.isMoveBilling()) {
                    return true;
                }

                var addressData, newBillingAddress;

                if ($('[name="billing-address-same-as-shipping"]').is(':checked')) {
                    if (this.isFormInline) {
                        var shippingAddress = quote.shippingAddress();
                        addressData = addressConverter.formAddressDataToQuoteAddress(
                            this.source.get('shippingAddress')
                        );
                        //Copy form data to quote shipping address object
                        for (var field in addressData) {
                            if (addressData.hasOwnProperty(field) &&
                                shippingAddress.hasOwnProperty(field) &&
                                typeof addressData[field] !== 'function' &&
                                _.isEqual(shippingAddress[field], addressData[field])
                            ) {
                                shippingAddress[field] = addressData[field];
                            } else if (typeof addressData[field] !== 'function' &&
                                !_.isEqual(shippingAddress[field], addressData[field])) {
                                shippingAddress = addressData;
                                break;
                            }
                        }

                        if (customer.isLoggedIn()) {
                            shippingAddress.save_in_address_book = 1;
                        }

                        newBillingAddress = addressConverter.formAddressDataToQuoteAddress(shippingAddress);
                        selectBillingAddress(newBillingAddress);
                    } else {
                        var billingAddress = quote.shippingAddress();
                        selectBillingAddress(billingAddress);
                    }

                    return true;
                }

                var selectedAddress = quote.billingAddress();
                if (selectedAddress) {
                    if (selectedAddress.customerAddressId) {
                        return addressList.some(function (address) {
                            if (selectedAddress.customerAddressId === address.customerAddressId) {
                                selectBillingAddress(address);
                                return true;
                            }
                            return false;
                        });
                    } else if (selectedAddress.getType() === 'new-customer-address' || selectedAddress.getType() ===
                        'new-billing-address') {
                        return true;
                    }
                }

                this.source.set('params.invalid', false);
                this.source.trigger('billingAddress.data.validate');

                if (this.source.get('billingAddress.custom_attributes')) {
                    this.source.trigger('billingAddress.custom_attributes.data.validate');
                }

                if (this.source.get('params.invalid')) {
                    return false;
                }

                addressData = this.source.get('billingAddress');

                if ($('#billing-save-in-address-book').is(':checked')) {
                    addressData.save_in_address_book = 1;
                }
                newBillingAddress = createBillingAddress(addressData);

                selectBillingAddress(newBillingAddress);

                return true;
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

            validateFullTaxInvoice: function () {
                if (!this.source.fullTaxInvoice || !this.source.fullTaxInvoice.use_full_tax) {
                    return true;
                }

                this.source.set('params.invalid', false);

                this.source.trigger('fullTaxInvoice.data.validate');
                if (this.source.get('fullTaxInvoice.custom_attributes')) {
                    this.source.trigger('fullTaxInvoice.custom_attributes.data.validate');
                }

                return !this.source.get('params.invalid');
            },

            /**
             * Show address form popup
             */
            submitForm: function (id, data) {
                $('#' + id).submit();
            }
        });
    };
});
