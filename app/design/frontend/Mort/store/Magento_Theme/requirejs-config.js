/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            "theme": 'Magento_Theme/js/theme',
            "selectize":    "js/selectize"
        }
    },
    paths: {
        "carouselInit":     'js/carouselInit',
        "blockCollapse":    'js/sidebarCollapse',
        "animateNumber":    'Magento_Theme/js/jquery.animateNumber.min',
        "owlcarousel":      'Magento_Theme/js/owl.carousel.min',
        "customSelect":     "Magento_Theme/js/select2",
        "doubleTap":        "Magento_Theme/js/doubletaptogo",
        "stickUpNav":       "Magento_Theme/js/stickUp"
    },
    shim: {
        "owlcarousel":      ["jquery"],
        "animateNumber":    ["jquery"],
        "doubleTap":        ["jquery", "jquery/ui"],
        "stickUpNav":        ["jquery"]
    },
    deps: [
        "jquery",
        "mage/common",
        "mage/dataPost",
        "mage/bootstrap"
    ]
};
