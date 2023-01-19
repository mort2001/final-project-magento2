/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (validator) {
        validator.addRule('validate-phone-number', function (v) {
            v = String(v);
            var thisRegex = new RegExp('^08|06|09+[\\d]{8}$');
            var thisRegex2 = new RegExp('^[0-9]{10}$');
            var thisRegex3 = new RegExp('^02|03|04|05|07+[\\d]{7}$');
            var thisRegex4 = new RegExp('^[0-9]{9}$');
            return (thisRegex.test(v) && thisRegex2.test(v)) || (thisRegex3.test(v) && thisRegex4.test(v));
        }, $.mage.__('Invalid phone number.'));

        return validator;
    };
});
