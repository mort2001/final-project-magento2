/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
        'jquery',
        'mage/translate',
        'underscore',
        'Magento_Catalog/js/product/view/product-ids-resolver',
        'Magento_Catalog/js/product/view/product-info-resolver',
        'jquery-ui-modules/widget',
    ], function ($, $t, _, idsResolver, productInfoResolver) {
        'use strict';
        return function (widget) {
            $.widget('mage.catalogAddToCart', widget, {
                options: {
                    processStart: null,
                    processStop: null,
                    bindSubmit: true,
                    minicartSelector: '[data-block="minicart"]',
                    messagesSelector: '[data-placeholder="messages"]',
                    productStatusSelector: '.stock.available',
                    addToCartButtonSelector: '.action.tocart',
                    addToCartButtonDisabledClass: 'disabled',
                    addToCartButtonTextWhileAdding: '',
                    addToCartButtonTextAdded: '',
                    addToCartButtonTextDefault: '',
                    productInfoResolver: productInfoResolver
                },

                /**
                 *
                 * @private
                 */
                _bindSubmit: function () {
                    var self = this;

                    if (this.element.data('catalog-addtocart-initialized')) {
                        return;
                    }

                    this.element.data('catalog-addtocart-initialized', 1);
                    this.element.on('submit', function (e) {
                        e.preventDefault();
                        self.submitForm($(this));
                    });
                },

                /**
                 * @private
                 */
                _redirect: function (url) {
                    var urlParts, locationParts, forceReload;

                    urlParts = url.split('#');
                    locationParts = window.location.href.split('#');
                    forceReload = urlParts[0] === locationParts[0];

                    window.location.assign(url);

                    if (forceReload) {
                        window.location.reload();
                    }
                },

                /**
                 * @return {Boolean}
                 */
                isLoaderEnabled: function () {
                    return this.options.processStart && this.options.processStop;
                },

                /**
                 *
                 * @param form
                 */
                submitForm: function (form) {
                    var productSku = form.data().productSku,
                        self = this;
                    $.ajax({
                        url: '/tigren_advancedcheckout/payment/index',
                        type: 'post',
                        data: {
                            productSku: productSku,
                        },
                        dataType: 'json',
                        beforeSend: function () {
                            $('body').trigger('processStart');
                        },
                        success: function (notice) {
                            if (notice.ClearCart === true) {
                                $('body').trigger('processStop');
                                var popup = $('<div class="add-to-cart-modal-popup"/>').html('<div style="color: #0a820b;"><span>This product has been added to cart before ...</span></div>').modal({
                                    modalClass: 'add-to-cart-popup',
                                    title: $.mage.__("<div style='color: #0a820b'><b>Notification</b></div>"),
                                    buttons: [
                                        {
                                            text: 'Continue Adding to cart',
                                            click: function () {
                                                $('body').trigger('processStop');
                                                self.ajaxSubmit(form);
                                                this.closeModal();
                                            }
                                        },
                                        {
                                            text: 'Clear Cart',
                                            click: function (status) {
                                                var customurl = "/tigren_advancedcheckout/payment/clearcart";
                                                $.ajax({
                                                    url: customurl,
                                                    data: {
                                                        status: "test1",
                                                    },
                                                    type: 'post',
                                                    dataType: 'json',
                                                    success: function (response) {
                                                        $('body').trigger('processStop');
                                                        console.log(response);
                                                    },
                                                })
                                                this.closeModal();
                                            }
                                        },
                                        {
                                            text: 'Add and Checkout',
                                            click: function () {
                                                self.ajaxSubmit(form);
                                                window.location = window.checkout.checkoutUrl
                                            }
                                        }
                                    ]
                                });
                                popup.modal('openModal');
                            } else {
                                $('body').trigger('processStop');
                                self.ajaxSubmit(form);
                            }
                        }
                    });
                },

                /**
                 *
                 * @param form
                 */
                ajaxSubmit: function (form) {
                    var self = this,
                        productIds = idsResolver(form),
                        productInfo = self.options.productInfoResolver(form),
                        formData;
                    $(self.options.minicartSelector).trigger('contentLoading');
                    self.disableAddToCartButton(form);
                    // var productSku = form.data().productSku;
                    formData = new FormData(form[0]);
                    $.ajax({
                        url: form.prop('action'),
                        data: formData,
                        type: 'POST',
                        dataType: 'json',
                        cache: false,
                        contentType: false,
                        processData: false,

                        /** @inheritdoc */
                        beforeSend: function () {
                            if (self.isLoaderEnabled()) {
                                $('body').trigger(self.options.processStart);
                            }
                        },

                        /** @inheritdoc */
                        success: function (res) {
                            var eventData, parameters;
                            $(document).trigger('ajax:addToCart', {
                                'sku': form.data().productSku,
                                'productIds': productIds,
                                'productInfo': productInfo,
                                'form': form,
                                'response': res
                            });

                            if (self.isLoaderEnabled()) {
                                $('body').trigger(self.options.processStop);
                            }
                            if (res.backUrl) {
                                eventData = {
                                    'form': form,
                                    'redirectParameters': []
                                };
                                // trigger global event, so other modules will be able to add parameters to redirect url
                                $('body').trigger('catalogCategoryAddToCartRedirect', eventData);
                                if (eventData.redirectParameters.length > 0 &&
                                    window.location.href.split(/[?#]/)[0] === res.backUrl

                                ) {
                                    parameters = res.backUrl.split('#');
                                    parameters.push(eventData.redirectParameters.join('&'));
                                    res.backUrl = parameters.join('#');
                                }
                                self._redirect(res.backUrl);

                                return;
                            }
                            if (res.messages) {
                                $(self.options.messagesSelector).html(res.messages);
                            }
                            if (res.minicart) {
                                $(self.options.minicartSelector).replaceWith(res.minicart);
                                $(self.options.minicartSelector).trigger('contentUpdated');
                            }
                            if (res.product && res.product.statusText) {
                                $(self.options.productStatusSelector)
                                    .removeClass('available')
                                    .addClass('unavailable')
                                    .find('span')
                                    .html(res.product.statusText);
                            }
                            self.enableAddToCartButton(form);
                        },

                        /** @inheritdoc */
                        error: function (res) {
                            $(document).trigger('ajax:addToCart:error', {
                                'sku': form.data().productSku,
                                'productIds': productIds,
                                'productInfo': productInfo,
                                'form': form,
                                'response': res
                            });
                        },

                        /** @inheritdoc */
                        complete: function (res) {
                            if (res.state() === 'rejected') {
                                location.reload();
                            }
                        }
                    });
                }
                ,
                /**
                 * @param {String} form
                 */
                disableAddToCartButton: function (form) {
                    var addToCartButtonTextWhileAdding = this.options.addToCartButtonTextWhileAdding || $t('Adding...'),
                        addToCartButton = $(form).find(this.options.addToCartButtonSelector);

                    addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
                    addToCartButton.find('span').text(addToCartButtonTextWhileAdding);
                    addToCartButton.prop('title', addToCartButtonTextWhileAdding);
                }
                ,

                /**
                 * @param {String} form
                 */
                enableAddToCartButton: function (form) {

                    var addToCartButtonTextAdded = this.options.addToCartButtonTextAdded || $t('Added'),
                        self = this,
                        addToCartButton = $(form).find(this.options.addToCartButtonSelector);

                    addToCartButton.find('span').text(addToCartButtonTextAdded);
                    addToCartButton.prop('title', addToCartButtonTextAdded);

                    setTimeout(function () {
                        var addToCartButtonTextDefault = self.options.addToCartButtonTextDefault || $t('Add to Cart');

                        addToCartButton.removeClass(self.options.addToCartButtonDisabledClass);
                        addToCartButton.find('span').text(addToCartButtonTextDefault);
                        addToCartButton.prop('title', addToCartButtonTextDefault);
                    }, 1000);
                }
            });

            return $.mage.catalogAddToCart;
        }
    }
)
;