/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define(
    [
    'jquery',
    'jquery/ui'
    ], function ($) {
        'use strict';

        $.widget(
            'tigren.qtyButton', {
                _create: function () {
                    this._bind();
                },

                _bind: function () {
                    $(window).on(
                        "load", function () {
                            $('.quantity-controls.quantity-plus').attr('disabled', true);
                            $('.quantity-controls.quantity-minus').attr('disabled', true);
                        }
                    );

                    $('.quantity-controls.quantity-plus').removeAttr('disabled');
                    $('.quantity-controls.quantity-minus').removeAttr('disabled');


                    $('.quantity-controls.quantity-minus').on(
                        "click", function (e) {
                            e.preventDefault();
                            var qty = parseInt($(this).parent().find('input[name$="[qty]"]').val()),
                            form = $('form#form-validate');
                            if (!qty || qty == 0) { qty = 1;
                            }
                            if (qty > 1) {
                                $(this).parent().find('input[name$="[qty]"]').val(qty - 1);
                            }
                        }
                    );

                    $('.quantity-controls.quantity-plus').on(
                        "click", function (e) {
                            e.preventDefault();
                            var qty = parseInt($(this).parent().find('input[name$="[qty]"]').val()),
                            form = $('form#form-validate');
                            if (!qty || qty == 0) { qty = 1;
                            }
                            $(this).parent().find('input[name$="[qty]"]').val(qty + 1);
                        }
                    );
                },
            }
        );
        return $.tigren.qtyButton;
    }
);