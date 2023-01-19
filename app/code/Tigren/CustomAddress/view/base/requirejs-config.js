/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

var config = {
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'Tigren_CustomAddress/js/validator-mixin': true
            },
            'Magento_Ui/js/lib/validation/rules': {
                'Tigren_CustomAddress/js/validation/rules-mixin': true
            },
            'Magento_Ui/js/form/element/abstract': {
                'Tigren_CustomAddress/js/form/element/abstract-mixin': true
            }
        }
    },
    map: {
        '*': {
            'addressEditFormLoader': 'Tigren_CustomAddress/js/customer/address/edit-form-loader'
        }
    }
};
