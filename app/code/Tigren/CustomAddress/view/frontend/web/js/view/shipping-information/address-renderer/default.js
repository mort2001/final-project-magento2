/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([], function () {
    'use strict';
    return function (Component) {
        return Component.extend({
            defaults: {
                template: 'Tigren_CustomAddress/shipping-information/address-renderer/default'
            }
        });
    };
});
