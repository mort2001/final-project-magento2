/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

/**
 * @api
 */
define([], function () {
    'use strict';

    /**
     * Returns new address object.
     *
     * @param {Object} addressData
     * @return {Object}
     */
    return function (addressData) {
        var regionId;

        if (addressData.region['region_id'] && addressData.region['region_id'] !== '0') {
            regionId = addressData.region['region_id'] + '';
        }

        return {
            customerAddressId: addressData.id ? addressData.id : addressData.customer_address_id,
            email: addressData.email,
            countryId: addressData['country_id'],
            regionId: regionId,
            regionCode: addressData.region['region_code'],
            region: addressData.region.region,
            customerId: addressData['customer_id'],
            street: addressData.street,
            company: addressData.company,
            telephone: addressData.telephone,
            fax: addressData.fax,
            postcode: addressData.postcode,
            city: addressData.city,
            firstname: addressData.firstname,
            lastname: addressData.lastname,
            middlename: addressData.middlename,
            prefix: addressData.prefix,
            suffix: addressData.suffix,
            vatId: addressData['vat_id'],
            sameAsBilling: addressData['same_as_billing'],
            saveInAddressBook: addressData['save_in_address_book'],
            customAttributes: addressData['custom_attributes'],

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
             * @return {*}
             */
            getAddressInline: function () {
                return addressData.inline;
            },

            /**
             * @return {String}
             */
            getType: function () {
                return 'customer-address';
            },

            /**
             * @return {String}
             */
            getKey: function () {
                return this.getType() + this.customerAddressId;
            },

            /**
             * @return {String}
             */
            getCacheKey: function () {
                return this.getKey();
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
