/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote'
], function (Component, customerData, quote) {
    'use strict';

    var countryData = customerData.get('directory-data');


    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/billing-information/address-renderer/default'
        },

        getBilling: function () {
            var fullTaxInvoice = JSON.parse(localStorage.getItem('full-tax-invoice'));
            return fullTaxInvoice ? fullTaxInvoice : {};
        },

        checkUsingFullTaxInvoice: function () {
            var fullTaxInvoice = JSON.parse(localStorage.getItem('full-tax-invoice'));
            if (!fullTaxInvoice|| fullTaxInvoice === undefined) {
                return false;
            }
            return fullTaxInvoice.is_full_invoice !== undefined;
        },

        checkUsingPersonal: function () {
            var fullTaxInvoice = JSON.parse(localStorage.getItem('full-tax-invoice'));
            if (!fullTaxInvoice || fullTaxInvoice === undefined) {
                return false;
            }
            return fullTaxInvoice.invoice_type === 'personal';
        },

        /**
         * @param {*} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] != undefined ? countryData()[countryId].name : ''; //eslint-disable-line
        }
    });
});
