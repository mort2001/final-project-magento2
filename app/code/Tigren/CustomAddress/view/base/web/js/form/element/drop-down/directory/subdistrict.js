/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'uiRegistry',
    'Tigren_CustomAddress/js/form/element/drop-down/directory-select'
], function (
    $,
    registry,
    Select
) {
    'use strict';

    return Select.extend({
        defaults: {
            modules: {
                parent: '${ $.parentName }',
                region: '${ $.parentName }.region_id',
                regionInput: '${ $.parentName }.region',
                city: '${ $.parentName }.city_id',
                cityInput: '${ $.parentName }.city',
                subdistrictInput: '${ $.parentName }.subdistrict',
                postcode: '${ $.parentName }.postcode',
                country: '${ $.parentName }.country_id'
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
                update: '${ $.parentName }.city_id:value'
            }
        },

        initialize: function () {
            this._super();
            this.validation['required-entry'] = true;
            var self = this;
            if (this.isBackend) {
                if (this.country() !== undefined) {
                    if (this.country().value() !== 'TH') {
                        this.visible(false);
                    }
                }
                if (this.city() !== undefined)
                    this.filter(this.city().value(), undefined);
                if (this.customScope !== 'billingAddress') {
                    $.each(this.options(), function (index, option) {
                        if (option.label === $(self.backendCreateOrderFields.shippingSubdistrictInput).val() ||
                            option.value === $(self.backendCreateOrderFields.shippingSubdistrictInput).val())
                            self.value(option.value);
                    });
                    if ($(this.backendCreateOrderFields.sameAsBillingInput).is(':checked'))
                        this.disabled(true);
                } else {
                    $.each(this.options(), function (index, option) {
                        if (option.label === $(self.backendCreateOrderFields.billingSubdistrictInput).val())
                            self.value(option.value);
                    });
                }
            }

            $(document).on('change', 'select[name="country_id"]', function (e) {
                $('.field.address-subdistrict').addClass('_required');
            }.bind(this));

            return this;
        },

        /**
         * @param {String} value
         */
        update: function (value) {
            var city = registry.get(this.parentName + '.' + 'city_id'),
                cityInput = this.cityInput(),
                options = city.indexedOptions,
                option;

            if (!value) {
                return;
            }

            option = options[value];
            if (typeof option === 'undefined') {
                return;
            }

            if (cityInput) {
                cityInput.value(option.label);
            }

            if (this.skipValidation) {
                this.validation['required-entry'] = false;
                this.required(false);
            } else {
                this.validation['required-entry'] = true;

                if (option && !this.options().length) {
                    registry.get(this.customName, function (input) {
                        input.validation['required-entry'] = true;
                        input.required(true);
                    });
                }

                this.required(true);
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
            var city = registry.get(this.parentName + '.' + 'city_id');

            if (city) {
                this._super(value, field);
            }

            this.reset();
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            var self = this;
            if (this.isBackend) {
                if (this.customScope === 'billingAddress') {
                    $(this.backendCreateOrderFields.billingSubdistrictInput).val(this.value());
                    if ($(this.backendCreateOrderFields.sameAsBillingInput).is(':checked')) {
                        $(this.backendCreateOrderFields.shippingSubdistrictInput).val(this.value());
                        $.each(this.options(), function (index, option) {
                            $(self.uiBackendFields.uiShippingSubdistrictIdInput).
                                append(new Option(option.label, option.value));
                        });
                        $(this.uiBackendFields.uiShippingSubdistrictIdInput).val(this.value());
                    }

                } else {
                    $(this.backendCreateOrderFields.shippingSubdistrictInput).val(this.value());
                }
            }
        }
    });
});
