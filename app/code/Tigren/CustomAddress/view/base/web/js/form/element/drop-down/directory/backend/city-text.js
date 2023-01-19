/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract'
], function (
    $,
    registry,
    Abstract
) {
    'use strict';

    return Abstract.extend({
        defaults: {
            modules: {
                parent: '${ $.parentName }',
                region: '${ $.parentName }.region_id',
                regionInput: '${ $.parentName }.region',
                cityInput: '${ $.parentName }.city',
                subdistrict: '${ $.parentName }.subdistrict_id',
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
            }
        },

        initialize: function () {
            this._super();

            if (this.isBackend) {
                if (this.customScope !== 'billingAddress') {
                    this.value($(this.backendCreateOrderFields.shippingCityInput).val());
                    if ($(this.backendCreateOrderFields.sameAsBillingInput).is(':checked'))
                        this.disabled(true);
                } else {
                    this.value($(this.backendCreateOrderFields.billingCityInput).val());
                }

                if (this.country() !== undefined) {
                    if (this.country().value() !== 'TH') {
                        this.visible(true);
                    } else {
                        this.visible(false);
                    }
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
                    $(this.backendCreateOrderFields.billingCityInput).val(this.value());
                    if ($(this.backendCreateOrderFields.sameAsBillingInput).attr('checked')) {
                        $(this.backendCreateOrderFields.shippingCityInput).val(this.value());
                        $(this.uiBackendFields.uiShippingCityInput).val(this.value());
                    }

                } else {
                    $(this.backendCreateOrderFields.shippingCityInput).val(this.value());
                }
            }
        }
    });
});
