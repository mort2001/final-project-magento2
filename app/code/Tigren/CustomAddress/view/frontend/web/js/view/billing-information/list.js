/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'ko',
    'mageUtils',
    'uiComponent',
    'uiLayout',
    'Magento_Checkout/js/model/quote'
], function ($, ko, utils, Component, layout, quote) {
    'use strict';

    var defaultRendererTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: 'Tigren_CustomAddress/js/view/billing-information/address-renderer/default'
    };

    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/billing-information/list',
            rendererTemplates: {}
        },

        /** @inheritdoc */
        initialize: function () {
            var self = this;

            this._super().initChildren();

            quote.billingAddress.subscribe(function (address) {
                self.createRendererComponent(address);
            });

            return this;
        },

        /** @inheritdoc */
        initConfig: function () {
            this._super();
            // the list of child components that are responsible for address rendering
            this.rendererComponents = {};

            return this;
        },

        /** @inheritdoc */
        initChildren: function () {
            return this;
        },

        /**
         * Create new component that will render given address in the address list
         *
         * @param {Object} address
         */
        createRendererComponent: function (address) {
            if (address === null) {
                return;
            }
            var rendererTemplate, templateData, rendererComponent;

            $.each(this.rendererComponents, function (index, component) {
                component.visible(false);
            });

            if (this.rendererComponents[address.getType()]) {
                this.rendererComponents[address.getType()].address(address);
                this.rendererComponents[address.getType()].visible(true);
            } else {
                // rendererTemplates are provided via layout
                rendererTemplate = address.getType() != undefined && this.rendererTemplates[address.getType()] !=
                undefined ? //eslint-disable-line
                    utils.extend({}, defaultRendererTemplate, this.rendererTemplates[address.getType()]) :
                    defaultRendererTemplate;
                templateData = {
                    parentName: this.name,
                    name: address.getType()
                };

                rendererComponent = utils.template(rendererTemplate, templateData);
                utils.extend(
                    rendererComponent,
                    {
                        address: ko.observable(address),
                        visible: ko.observable(true)
                    }
                );
                layout([rendererComponent]);
                this.rendererComponents[address.getType()] = rendererComponent;
            }
        }
    });
});
