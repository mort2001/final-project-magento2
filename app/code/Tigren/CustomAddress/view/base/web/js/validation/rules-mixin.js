/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    return function (targetModule) {
        return _.extend(targetModule, {
            min_text_length: {
                handler: function (value, params) {
                    return !value || value.length === 0 || value.length >= +params;
                },
                message: $.mage.__('Please enter more or equal than {0} symbols.')
            },
            max_text_length: {
                handler: function (value, params) {
                    return !value || value.length <= +params;
                },
                message: $.mage.__('Please enter less or equal than {0} symbols.')
            }
        });
    };
});
