/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'ko',
    'underscore'
], function ($, ko, _) {
    'use strict';

    return {
        isBillingSameShipping: ko.observable(true),
        clickNewAddress: ko.observable(true)
    };
});
