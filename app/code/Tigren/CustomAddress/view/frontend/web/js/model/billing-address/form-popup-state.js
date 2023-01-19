/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'ko'
], function (ko) {
    'use strict';

    return {
        isVisible: ko.observable(false),
        hasUpdatedAddress: ko.observable(false),
        isNewAddress: ko.observable(false)
    };
});
