/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

/**
 * @api
 */
define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/default-post-code-resolver'
], function ($, _, registry, Select, defaultPostCodeResolver) {
    'use strict';

    return Select.extend({
        defaults: {
            modules: {
                subdistrict: '${ $.parentName }.subdistrict_id',
                subdistrictInput: '${ $.parentName }.subdistrict',
                city: '${ $.parentName }.city_id',
                cityInput: '${ $.parentName }.city',
                postcode: '${ $.parentName }.postcode',
                countryIdInput: '${ $.parentName }.country_id'
            },
            backendCreateOrderFields: {
                billingCountryIdInput: '#order-billing_address_country_id',
                billingRegionInput: '#order-billing_address_region',
                billingRegionIdInput: '#order-billing_address_region_id',
                billingCityInput: '#order-billing_address_city',
                billingPostcodeInput: '#order-billing_address_postcode',
                billingSubdistrictInput: '#order-billing_address_subdistrict',

                shippingCountryIdInput: '#order-shipping_address_country_id',
                shippingRegionInput: '#order-shipping_address_region',
                shippingRegionIdInput: '#order-shipping_address_region_id',
                shippingCityInput: '#order-shipping_address_city',
                shippingPostcodeInput: '#order-shipping_address_postcode',
                shippingSubdistrictInput: '#order-shipping_address_subdistrict',

                sameAsBillingInput: '#order-shipping_same_as_billing'
            },
            uiBackendFields: {
                uiShippingCountryIdContainer: '.shipping-address-country_id',
                uiShippingCountryIdInput: '.shipping-address-country_id select[name="country_id"]',

                uiShippingRegionContainer: '.shipping-address-region',
                uiShippingRegionInput: '.shipping-address-region input[name="region"]',

                uiShippingRegionIdContainer: '.shipping-address-region_id',
                uiShippingRegionIdInput: '.shipping-address-region_id select[name="region_id"]',

                uiShippingCityContainer: '.shipping-address-city',
                uiShippingCityInput: '.shipping-address-city input[name="city"]',

                uiShippingCityIdContainer: '.shipping-address-city_id',
                uiShippingCityIdInput: '.shipping-address-city_id select[name="custom_attributes[city_id]"]',

                uiShippingPostcodeContainer: '.shipping-address-postcode',
                uiShippingPostcodeInput: '.shipping-address-postcode input[name="postcode"]',

                uiShippingSubdistrictContainer: '.shipping-address-subdistrict',
                uiShippingSubdistrictInput: '.shipping-address-subdistrict input[name="custom_attributes[subdistrict]"]',

                uiShippingSubdistrictIdContainer: '.shipping-address-subdistrict_id',
                uiShippingSubdistrictIdInput: '.shipping-address-subdistrict_id select[name="custom_attributes[subdistrict_id]"]'
            },
            skipValidation: false,
            imports: {
                update: '${ $.parentName }.country_id:value'
            }
        },

        initialize: function () {
            this._super();
            if (this.isBackend) {
                if (this.countryIdInput() !== undefined)
                    this.filter(this.countryIdInput().value(), undefined);
                if (this.customScope !== 'billingAddress') {
                    this.value($(this.backendCreateOrderFields.shippingRegionIdInput).val());
                    if ($(this.backendCreateOrderFields.sameAsBillingInput).is(':checked'))
                        this.disabled(true);
                } else {
                    this.value($(this.backendCreateOrderFields.billingRegionIdInput).val());
                }
            }
            return this;
        },

        /**
         * @param {String} value
         */
        update: function (value) {
            var country = registry.get(this.parentName + '.' + 'country_id'),
                options = country.indexedOptions,
                isRegionRequired,
                option;

            if (!value) {
                return;
            }
            option = options[value];

            if (typeof option === 'undefined') {
                return;
            }

            defaultPostCodeResolver.setUseDefaultPostCode(!option['is_zipcode_optional']);

            if (this.skipValidation) {
                this.validation['required-entry'] = false;
                this.required(false);
            } else {
                if (option && !option['is_region_required']) {
                    this.error(false);
                    this.validation = _.omit(this.validation, 'required-entry');
                } else {
                    this.validation['required-entry'] = true;
                }

                if (option && !this.options().length) {
                    registry.get(this.customName, function (input) {
                        isRegionRequired = !!option['is_region_required'];
                        input.validation['required-entry'] = isRegionRequired;
                        input.required(isRegionRequired);
                    });
                }

                this.required(!!option['is_region_required']);

                if (option && option.value === 'TH' && this.subdistrict() && this.subdistrictInput() && this.city() &&
                    this.cityInput() && this.postcode()) {
                    this.cityInput().validation = _.omit(this.cityInput().validation, 'required-entry');
                    this.cityInput().required(false);
                    this.cityInput().visible(false);
                    this.cityInput().reset();

                    this.city().validation = _.extend(this.city().validation, { 'required-entry': true });
                    this.city().required(true);
                    this.city().visible(true);
                    this.city().reset();

                    this.subdistrictInput().visible(false);
                    this.subdistrictInput().validation = _.omit(this.subdistrictInput().validation, 'required-entry');
                    this.subdistrictInput().required(false);
                    this.subdistrictInput().reset();

                    this.subdistrict().visible(true);
                    this.subdistrict().validation = _.extend(this.subdistrict().validation, { 'required-entry': true });
                    this.subdistrict().required(true);
                    this.subdistrict().reset();

                    this.postcode().reset();
                } else if (option && option.value !== 'TH' && this.subdistrict() && this.subdistrictInput() &&
                    this.city() && this.cityInput() && this.postcode()) {
                    this.cityInput().validation = _.extend(this.cityInput().validation, { 'required-entry': true });
                    this.cityInput().required(true);
                    this.cityInput().visible(true);
                    this.cityInput().reset();

                    this.city().validation = _.omit(this.city().validation, 'required-entry');
                    this.city().required(false);
                    this.city().visible(false);
                    this.city().reset();

                    this.subdistrictInput().visible(true);
                    this.subdistrictInput().validation = _.extend(this.subdistrictInput().validation,
                        { 'required-entry': true });
                    this.subdistrictInput().required(false);
                    this.subdistrictInput().reset();

                    this.subdistrict().visible(false);
                    this.subdistrict().validation = _.omit(this.subdistrict().validation, 'required-entry');
                    this.subdistrict().required(false);
                    this.subdistrict().reset();

                    this.postcode().reset();
                }
            }
        },

        /**
         * Filters 'initialOptions' property by 'field' and 'value' passed,
         * calls 'setOptions' passing the result to it
         *
         * @param {*} value
         * @param {String} field
         */
        filter: function (value, field) {
            var country = registry.get(this.parentName + '.' + 'country_id'),
                option;

            if (country) {
                option = country.indexedOptions[value];

                this._super(value, field);

                if (option && option['is_region_visible'] === false) {
                    // hide select and corresponding text input field if region must not be shown for selected country
                    this.setVisible(false);

                    if (this.customEntry) {// eslint-disable-line max-depth
                        this.toggleInput(false);
                    }
                }
            }
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            var self = this;
            if (this.isBackend) {
                if (this.customScope === 'billingAddress') {
                    $(this.backendCreateOrderFields.billingRegionIdInput).val(this.value());
                    // $(this.backendCreateOrderFields.billingRegionIdInput).val(this.source.billingAddress.city);
                    if ($(this.backendCreateOrderFields.sameAsBillingInput).is(':checked')) {
                        $(this.backendCreateOrderFields.shippingRegionIdInput).val(this.value());
                        // $(this.backendCreateOrderFields.shippingRegionIdInput).val(this.source.billingAddress.city);
                        $.each(this.options(), function (index, option) {
                            $(self.uiBackendFields.uiShippingRegionIdInput).
                                append(new Option(option.label, option.value));
                        });
                        $(this.uiBackendFields.uiShippingRegionIdInput).val(this.value());
                    }

                } else {
                    $(this.backendCreateOrderFields.shippingRegionIdInput).val(this.source.billingAddress.city);
                }
            }
        }
    });
});

