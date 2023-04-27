/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

var config = {
    map: {
        '*': {
            "Magento_Checkout/js/view/payment/default": "Tigren_AdvancedCheckout/js/view/payment/default"
        }
    },
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'Tigren_AdvancedCheckout/js/catalog-add-to-cart': true
            },
        }
    }
};