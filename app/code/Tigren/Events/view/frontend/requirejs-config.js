/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

var config = {
    paths: {
        fullcalendar: 'Tigren_Events/js/fullcalendar',
        moment: 'Tigren_Events/js/moment.min',
        fancybox: 'Tigren_Events/js/jquery.fancybox',
        locale_all: 'Tigren_Events/js/locale-all'
    },
    shim: {
        'fancybox': {
            deps: ['jquery']
        }
    },

    config: {
        mixins: {
            'Magento_Checkout/js/model/quote' : {
                'Tigren_Events/js/model/quote-mixin': true
            }
        }
    }
};