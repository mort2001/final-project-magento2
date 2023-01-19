/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'Tigren_CustomAddress/js/view/shipping': true
            },
            'Magento_Checkout/js/action/set-shipping-information': {
                'Tigren_CustomAddress/js/action/set-shipping-information-mixin': true
            },
            'Magento_Checkout/js/view/shipping-information/address-renderer/default': {
                'Tigren_CustomAddress/js/view/shipping-information/address-renderer/default': true
            },
            'Magento_Checkout/js/action/set-billing-address': {
                'Tigren_CustomAddress/js/action/set-billing-address-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Tigren_CustomAddress/js/action/place-order-mixin': true
            },
            'Temando_Shipping/js/view/checkout/shipping-information/address-renderer/shipping': {
                'Tigren_CustomAddress/js/view/checkout/shipping-information/address-renderer/shipping': true
            }
        }
    },
    map: {
        '*': {
            'cityUpdater': 'Tigren_CustomAddress/js/city-updater',
            'subdistrictUpdater': 'Tigren_CustomAddress/js/subdistrict-updater',
            'Magento_Checkout/js/model/quote': 'Tigren_CustomAddress/js/model/quote',
            'Magento_Customer/js/model/customer-addresses': 'Tigren_CustomAddress/js/model/customer-addresses',
            'Magento_Customer/js/model/customer/address': 'Tigren_CustomAddress/js/model/customer/address',
            'Magento_Checkout/js/model/new-customer-address': 'Tigren_CustomAddress/js/model/new-customer-address',
            'Magento_Checkout/js/view/shipping-address/list': 'Tigren_CustomAddress/js/view/shipping-address/list',
            'Magento_Checkout/js/view/shipping-address/address-renderer/default': 'Tigren_CustomAddress/js/view/shipping-address/address-renderer/default',
            'Magento_Checkout/js/model/shipping-address/form-popup-state': 'Tigren_CustomAddress/js/model/shipping-address/form-popup-state',
            'Magento_Checkout/js/action/select-shipping-address': 'Tigren_CustomAddress/js/action/select-shipping-address',
            'Magento_Checkout/js/action/create-billing-address': 'Tigren_CustomAddress/js/action/create-billing-address',
            'Magento_Checkout/js/action/select-billing-address': 'Tigren_CustomAddress/js/action/select-billing-address',
            'Magento_Checkout/js/model/address-converter': 'Tigren_CustomAddress/js/model/address-converter'
        }
    }
};

