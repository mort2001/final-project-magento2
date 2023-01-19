/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'ko',
    'Magento_Customer/js/model/address-list'
], function (ko, addressList) {
    'use strict';

    return function (address) {
        addressList().some(function (currentAddress, index, addresses) {
            if (currentAddress.getKey() === address.getKey()) {
                addressList.replace(currentAddress, address);
            }
        });

        addressList.valueHasMutated();

        return address;
    };
});
