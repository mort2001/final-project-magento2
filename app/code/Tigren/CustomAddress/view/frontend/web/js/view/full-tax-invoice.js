/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'ko',
    'underscore',
    'matchMedia',
    'mage/utils/objects',
    'Magento_Ui/js/form/form',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote'
], function (
    $,
    ko,
    _,
    mediaCheck,
    mageUtils,
    Component,
    customer,
    quote
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/full-tax-invoice',
            fullTaxInvoiceFormTemplate: 'Tigren_CustomAddress/full-tax-invoice/form',
            modules: {
                personalFirstName: '${ $.name }.additional-fieldsets.personal_firstname',
                personalLastName: '${ $.name }.additional-fieldsets.personal_lastname',
                taxIdentificationNumber: '${ $.name }.additional-fieldsets.tax_identification_number',
                company: '${ $.name }.additional-fieldsets.company',
                companyBranch: '${ $.name }.additional-fieldsets.company_branch'
            }
        },
        visible: ko.observable(!quote.isVirtual()),
        isUseFullTaxInvoice: ko.observable(false),
        isCustomerLoggedIn: customer.isLoggedIn,

        /**
         * @return {exports.initObservable}
         */
        initObservable: function () {
            this._super().observe([
                'headOffice',
                'branch'
            ]);

            this.isUseFullTaxInvoice.subscribe(function (isUseFullTaxInvoice) {
                this.source.fullTaxInvoice.use_full_tax = isUseFullTaxInvoice;
            }, this);

            return this;
        },

        initTaxInvoice: function () {
            this.personalFirstName().visible(true);
            this.personalFirstName().disabled(false);
            this.personalLastName().visible(true);
            this.personalLastName().disabled(false);
            this.taxIdentificationNumber().visible(true);
        },

        toggleInvoiceType: function (type) {
            if (type === 'personal') {
                this.personalFirstName().visible(true);
                this.personalFirstName().disabled(false);
                this.personalLastName().visible(true);
                this.personalLastName().disabled(false);
                this.taxIdentificationNumber().visible(true);
                this.company().visible(false);
                this.company().disabled(false);
                this.companyBranch().visible(false);
                this.companyBranch().disabled(false);
            }
            if (type === 'corporate') {
                this.personalFirstName().visible(false);
                this.personalFirstName().disabled(true);
                this.personalLastName().visible(false);
                this.personalLastName().disabled(true);
                this.taxIdentificationNumber().visible(true);
                this.company().visible(true);
                this.company().disabled(false);
                this.companyBranch().visible(true);
                this.companyBranch().disabled(false);
            }
            this.source.fullTaxInvoice.invoice_type = type;
        },

        toggleTypeBranch: function (type) {
            if (type === 'head') {
                this.headOffice(true);
                this.branch(false);
                this.source.fullTaxInvoice.company_branch = 'head';
            }
            if (type === 'branch') {
                this.headOffice(false);
                this.branch(true);
                this.source.fullTaxInvoice.company_branch = 'branch';
            }
        }
    });
});
