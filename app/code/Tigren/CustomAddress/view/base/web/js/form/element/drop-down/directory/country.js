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
                region: '${ $.parentName }.region',
                regionInput: '${ $.parentName }.region_id',
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
            }
        },

        initialize: function () {
            this._super();
            if (this.isBackend) {
                if (this.customScope === 'billingAddress')
                    this.value($(this.backendCreateOrderFields.billingCountryIdInput).val());
                else {
                    if ($(this.backendCreateOrderFields.sameAsBillingInput).is(':checked'))
                        this.disabled(true);
                    this.value($(this.backendCreateOrderFields.shippingCountryIdInput).val());
                }

                if (this.value !== 'TH') {
                    if (this.regionInput() !== undefined) this.regionInput().visible(true);
                    if (this.subdistrict() !== undefined) this.subdistrict().visible(true);
                    if (this.city() !== undefined) this.city().visible(true);
                } else {
                    if (this.regionInput() !== undefined) this.regionInput().visible(false);
                    if (this.subdistrict() !== undefined) this.subdistrict().visible(false);
                    if (this.city() !== undefined) this.city().visible(false);
                }
            }
            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            if (this.isBackend) {
                if (this.customScope === 'billingAddress') {
                    $(this.backendCreateOrderFields.billingCountryIdInput).val(this.value());
                    if ($(this.backendCreateOrderFields.sameAsBillingInput).is(':checked')) {
                        $(this.backendCreateOrderFields.shippingCountryIdInput).val(this.value());
                        $(this.uiBackendFields.uiShippingCountryIdInput).val(this.value());
                        if (this.value() === 'TH') {
                            $(this.uiBackendFields.uiShippingRegionIdContainer).show();
                            $(this.uiBackendFields.uiShippingCityIdContainer).show();
                            $(this.uiBackendFields.uiShippingSubdistrictIdContainer).show();
                            $(this.uiBackendFields.uiShippingRegionContainer).hide();
                            $(this.uiBackendFields.uiShippingCityContainer).hide();
                            $(this.uiBackendFields.uiShippingSubdistrictContainer).hide();
                        } else {
                            $(this.uiBackendFields.uiShippingRegionIdContainer).hide();
                            $(this.uiBackendFields.uiShippingCityIdContainer).hide();
                            $(this.uiBackendFields.uiShippingSubdistrictIdContainer).hide();
                            $(this.uiBackendFields.uiShippingRegionContainer).show();
                            $(this.uiBackendFields.uiShippingCityContainer).show();
                            $(this.uiBackendFields.uiShippingSubdistrictContainer).show();
                        }
                    }
                    if (this.value() === 'TH') {
                        $(this.backendCreateOrderFields.billingRegionIdInput).attr('disabled', false);
                    } else {
                        $(this.backendCreateOrderFields.billingRegionIdInput).attr('disabled', true);
                    }
                } else {
                    $(this.backendCreateOrderFields.shippingCountryIdInput).val(this.value());
                    if (this.value() === 'TH') {
                        $(this.backendCreateOrderFields.billingRegionIdInput).attr('disabled', false);
                    } else {
                        $(this.backendCreateOrderFields.billingRegionIdInput).attr('disabled', true);
                    }
                }
            }
        }
    });
});

