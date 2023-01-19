/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
/**
 * @api
 */
define(
    [
        'ko',
        'underscore',
        'mage/utils/wrapper'
    ], function (ko, _, wrapper) {
        'use strict';

        var quoteData = window.checkoutConfig.quoteData;

        return function (quoteTarget) {
            quoteTarget.isVirtual = wrapper.wrapSuper(quoteTarget.isVirtual, function () {
                return !!Number(quoteData['is_virtual']) || quoteData['isEvent'];
            });

            return quoteTarget;
        };
    }
);
