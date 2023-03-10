/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter'
], function (addressList, addressConverter) {
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

        if (!isAddressUpdated) {
            addressList.push(address);
        } else {
            addressList.valueHasMutated();
        }

        return address;
    };
});
