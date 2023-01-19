/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'underscore'
], function (_) {
    'use strict';

    return function (targetModule) {
        return targetModule.extend({
            /**
             * Callback that fires when 'value' property is updated.
             */
            onUpdate: function () {
                this.bubble('update', this.hasChanged());

                var rules = this.validation = this.validation || {};
                if (!!rules['validate-phone-number']) {
                    var telephone = this.value();
                    if (telephone.length > 10) {
                        this.value(telephone.substr(0, 10));
                    } else {
                        this.value(telephone.replace(/\D/g, ''));
                    }
                }

                this.validate();
            }
        });
    };
});
