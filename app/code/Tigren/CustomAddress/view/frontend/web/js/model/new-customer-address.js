/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
/**
 * @api
 */
define([
    'underscore',
    'Magento_Checkout/js/model/default-post-code-resolver'
], function (_, DefaultPostCodeResolver) {
    'use strict';

    /**
     * @param {Object} addressData
     * Returns new address object
     */
    return function (addressData) {
        var identifier = Date.now(),
            regionId;

        if (addressData.region && addressData.region['region_id']) {
            regionId = addressData.region['region_id'];
        } else if (addressData['country_id'] && addressData['country_id'] == window.checkoutConfig.defaultCountryId) { //eslint-disable-line
            regionId = window.checkoutConfig.defaultRegionId || undefined;
        }

        return {
            email: addressData.email,
            countryId: addressData['country_id'] || addressData.countryId || window.checkoutConfig.defaultCountryId,
            regionId: regionId || addressData.regionId,
            regionCode: addressData.region ? addressData.region['region_code'] : null,
            region: addressData.region ? addressData.region.region : null,
            customerId: addressData['customer_id'] || addressData.customerId,
            street: addressData.street ? _.compact(addressData.street) : addressData.street,
            company: addressData.company,
            telephone: addressData.telephone,
            fax: addressData.fax,
            postcode: addressData.postcode ? addressData.postcode : DefaultPostCodeResolver.resolve(),
            city: addressData.city,
            firstname: addressData.firstname,
            lastname: addressData.lastname,
            middlename: addressData.middlename,
            prefix: addressData.prefix,
            suffix: addressData.suffix,
            vatId: addressData['vat_id'],
            saveInAddressBook: addressData['save_in_address_book'],
            customAttributes: addressData['custom_attributes']
                ? addressData['custom_attributes']
                : addressData['customAttributes'],

            /**
             * @return {*}
             */
            isDefaultShipping: function () {
                return addressData['default_shipping'];
            },

            /**
             * @return {*}
             */
            isDefaultBilling: function () {
                return addressData['default_billing'];
            },

            /**
             * @return {String}
             */
            getType: function () {
                return addressData.type ? addressData.type : 'new-customer-address';
            },

            /**
             * @return {String}
             */
            getKey: function () {
                return this.getType();
            },

            /**
             * @return {String}
             */
            getCacheKey: function () {
                return this.getType() + identifier;
            },

            /**
             * @return {Boolean}
             */
            isEditable: function () {
                return true;
            },

            /**
             * @return {Boolean}
             */
            canUseForBilling: function () {
                return true;
            }
        };
    };
});
