/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Ui/js/model/messages',
    'uiLayout',
    'mage/url',
    'mage/translate'
], function (
    $,
    _,
    Component,
    ko,
    Messages,
    layout,
    url,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/customer/address/edit-form'
        },

        initialize: function () {
            this._super().initChildren();
            return this;
        },

        initChildren: function () {
            this.messageContainer = new Messages();
            this.createMessagesComponent();
            return this;
        },

        createMessagesComponent: function () {
            var messagesComponent = {
                parent: this.name,
                name: this.name + '.messages',
                displayArea: 'messages',
                component: 'Magento_Ui/js/view/messages',
                config: {
                    messageContainer: this.messageContainer
                }
            };

            layout([messagesComponent]);

            return this;
        },

        saveAddress: function () {
            var self = this;

            if (!this.validateAddressForm()) {
                return false;
            }

            var dataPost = _.extend(self.source.address, {
                form_key: $.mage.cookies.get('form_key')
            });

            $.ajax({
                url: url.build('custom_address/address/formPost'),
                method: 'post',
                data: dataPost,
                datatype: 'json',

                /** @inheritdoc */
                beforeSend: function () {
                    $('body').trigger('processStart');
                },

                complete: function () {
                    $('body').trigger('processStop');
                },

                success: function (response) {
                    if (response.success) {
                        window.location.href = url.build('customer/address');
                    } else {
                        self.messageContainer.addErrorMessage({
                            message: response.message
                        });
                    }
                },

                error: function (response) {
                    self.messageContainer.addErrorMessage({
                        message: response.message
                    });
                }
            });
        },

        backToAddressList: function () {
            window.location.href = url.build('customer/address');
        },

        /**
         * @return {Boolean}
         */
        validateAddressForm: function () {
            this.source.set('params.invalid', false);

            this.source.trigger('address.data.validate');
            if (this.source.get('address.custom_attributes')) {
                this.source.trigger('address.custom_attributes.data.validate');
            }

            if (!this.source.get('params.invalid')) {
                return true;
            }

            this.focusInvalid();
            $('.field.address-subdistrict').addClass('_required');

            return false;
        }
    });
});
