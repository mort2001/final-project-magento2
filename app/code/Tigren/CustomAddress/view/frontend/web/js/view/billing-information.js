/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/sidebar'
], function ($, Component, quote, stepNavigator, sidebarModel) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/billing-information'
        },

        /**
         * @return {Boolean}
         */
        isVisible: function () {
            return !quote.isVirtual() && stepNavigator.isProcessed('shipping') && quote.fullTaxInvoiceEnabled();
        },

        /**
         * Back step.
         */
        back: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('shipping');
        },

        /**
         * Back to shipping method.
         */
        backToShippingMethod: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('shipping', 'opc-shipping_method');
        }
    });
});
