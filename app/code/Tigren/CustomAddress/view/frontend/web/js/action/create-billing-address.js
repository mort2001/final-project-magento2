/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote'
], function (addressList, addressConverter, quote) {
    'use strict';

    return function (addressData) {
        var address = addressConverter.formAddressDataToQuoteAddress(addressData),
            isAddressUpdated = addressList().some(function (currentAddress, index, addresses) {
                if (currentAddress.getKey() == address.getKey()) { //eslint-disable-line eqeqeq
                    addresses[index] = address;

                    return true;
                }

                return false;
            });

        if (quote.isMoveBilling() && !quote.isVirtual() && isCustomerLoggedIn === true
            && window.checkoutConfig.customerData.default_shipping != null) {
            address['isBillingAddress'] = true;
        }

        if (!isAddressUpdated) {
            addressList.push(address);
        } else {
            addressList.valueHasMutated();
        }

        return address;
    };
});
