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
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setBillingAddressAction) {

        return wrapper.wrap(setBillingAddressAction, function (originalAction) {
            var billingAddress = quote.billingAddress();
            if (billingAddress && billingAddress.customAttributes) {
                if (billingAddress['extensionAttributes'] === undefined) {
                    billingAddress['extensionAttributes'] = {};
                }
                billingAddress['extensionAttributes']['city_id'] = billingAddress.customAttributes['city_id'];
                billingAddress['extensionAttributes']['subdistrict'] = billingAddress.customAttributes['subdistrict'];
                billingAddress['extensionAttributes']['subdistrict_id'] = billingAddress.customAttributes['subdistrict_id'];
            }
            // pass execution to original action ('Magento_Checkout/js/action/set-billing-address')
            return originalAction();
        });
    };
});
