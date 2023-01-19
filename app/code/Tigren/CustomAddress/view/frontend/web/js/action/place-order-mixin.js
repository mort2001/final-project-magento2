/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'Tigren_CustomAddress/js/model/tmp-checkout-data'
], function ($, wrapper, quote, registry, tmpCheckoutData) {
    'use strict';

    return function (placeOrderAction) {

        return wrapper.wrap(placeOrderAction, function (originalAction) {
            var isMoveBilling = quote.isMoveBilling(),
                source = registry.get('checkoutProvider'),
                billingAddress = quote.billingAddress() ? quote.billingAddress() : {},
                billingAddressCode = isMoveBilling ? 'billingAddressshared' : 'billingAddress',
                sourceBillingAddress = (typeof (source.get(billingAddressCode)) !== 'undefined') &&
                source.get(billingAddressCode).region_id
                    ? source.get(billingAddressCode)
                    : billingAddress;

            if (!tmpCheckoutData.isBillingSameShipping()) {
                source.set('params.invalid', false);
                source.trigger('billingAddress.data.validate');
                if (source.get('billingAddress.custom_attributes')) {
                    source.trigger('billingAddress.custom_attributes.data.validate');
                }

                sourceBillingAddress['countryId'] = sourceBillingAddress['country_id']
                    ? sourceBillingAddress['country_id']
                    : billingAddress['countryId'];
                sourceBillingAddress['customAttributes'] = sourceBillingAddress['custom_attributes']
                    ? sourceBillingAddress['custom_attributes']
                    : billingAddress['customAttributes'];
                sourceBillingAddress['extensionAttributes'] = sourceBillingAddress['custom_attributes']
                    ? sourceBillingAddress['custom_attributes']
                    : billingAddress['customAttributes'];
                sourceBillingAddress['regionId'] = sourceBillingAddress['region_id']
                    ? sourceBillingAddress['region_id']
                    : billingAddress['regionId'];
                delete sourceBillingAddress['country_id'];
                delete sourceBillingAddress['custom_attributes'];
                delete sourceBillingAddress['region_id'];
                delete sourceBillingAddress['type'];

                $.each(sourceBillingAddress, function (key, value) {
                    billingAddress[key] = value;
                });
            }

            if (quote.isMoveBilling()) {
                if (billingAddress && billingAddress.customAttributes) {
                    if (billingAddress['extensionAttributes'] === undefined) {
                        billingAddress['extensionAttributes'] = {};
                    }
                    billingAddress['extensionAttributes']['city_id'] = billingAddress.customAttributes['city_id'];
                    billingAddress['extensionAttributes']['subdistrict'] = billingAddress.customAttributes['subdistrict'];
                    billingAddress['extensionAttributes']['subdistrict_id'] = billingAddress.customAttributes['subdistrict_id'];
                }
            }

            var fullTaxInvoice = source.get('fullTaxInvoice');
            if (fullTaxInvoice && fullTaxInvoice.use_full_tax) {
                if (billingAddress['extensionAttributes'] === undefined) {
                    billingAddress['extensionAttributes'] = {};
                }
                billingAddress['extensionAttributes']['is_full_invoice'] = 1;
                if (fullTaxInvoice.invoice_type === '' ||
                    fullTaxInvoice.invoice_type === 'personal') {
                    billingAddress['extensionAttributes']['personal_firstname'] = fullTaxInvoice.personal_firstname;
                    billingAddress['extensionAttributes']['personal_lastname'] = fullTaxInvoice.personal_lastname;
                } else {
                    billingAddress['extensionAttributes']['head_office'] = fullTaxInvoice.company_branch === 'head'
                        ? '00000'
                        : null;
                    billingAddress['extensionAttributes']['branch_office'] = fullTaxInvoice.company_branch === 'branch'
                        ? $('input[name="more_info"]').
                            val()
                        : null;
                    billingAddress['extensionAttributes']['company'] = fullTaxInvoice.company;
                }
                billingAddress['extensionAttributes']['tax_identification_number'] = fullTaxInvoice.tax_identification_number;
                billingAddress['extensionAttributes']['invoice_type'] = fullTaxInvoice.invoice_type
                    ? fullTaxInvoice.invoice_type
                    : 'personal';

                if (
                    fullTaxInvoice.invoice_type !== 'corporate'
                    && fullTaxInvoice.personal_firstname
                    && fullTaxInvoice.personal_lastname
                ) {
                    billingAddress['firstname'] = fullTaxInvoice.personal_firstname;
                    billingAddress['lastname'] = fullTaxInvoice.personal_lastname;
                }
                billingAddress['telephone'] = fullTaxInvoice.telephone;
            }

            quote.billingAddress(billingAddress);

            // pass execution to original action ('Magento_Checkout/js/action/place-order')
            return originalAction();
        });
    };
});
