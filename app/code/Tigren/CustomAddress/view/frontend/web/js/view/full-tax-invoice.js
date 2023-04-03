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
    var fullTaxInvoiceFromDataBase  = window.checkoutConfig.quoteData.full_tax_invoice;
    return Component.extend({
        defaults: {
            template: 'Tigren_CustomAddress/full-tax-invoice',
            fullTaxInvoiceFormTemplate: 'Tigren_CustomAddress/full-tax-invoice/form',
            modules: {
                personalFirstName: '${ $.name }.additional-fieldsets.personal_firstname',
                personalLastName: '${ $.name }.additional-fieldsets.personal_lastname',
                taxIdentificationNumber: '${ $.name }.additional-fieldsets.tax_identification_number',
                company: '${ $.name }.additional-fieldsets.company',
                companyBranch: '${ $.name }.additional-fieldsets.company_branch',
                telephone: '${ $.name }.additional-fieldsets.telephone'
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
                'branchName',
                'headOffice',
                'branch'
            ]);
            // if(fullTaxInvoiceFromDataBase.is_full_invoice === '1'){
            //     this.isUseFullTaxInvoice(true);
            // }
            this.isUseFullTaxInvoice.subscribe(function (isUseFullTaxInvoice) {
                this.source.fullTaxInvoice.use_full_tax = isUseFullTaxInvoice;
                if(isUseFullTaxInvoice === true) {
                    this.source.fullTaxInvoice.invoice_type = 'personal';
                } else {
                    this.ignoreAllFields();
                }
            }, this);

            return this;
        },

        initTaxInvoice: function () {
            this.personalFirstName().visible(true);
            this.personalFirstName().disabled(false);
            this.personalLastName().visible(true);
            this.personalLastName().disabled(false);
            this.taxIdentificationNumber().visible(true);
            this.telephone().visible(true);
            this.telephone().disabled(false);
            if(fullTaxInvoiceFromDataBase.is_full_invoice === '1'){
                this.taxIdentificationNumber().value(fullTaxInvoiceFromDataBase.tax_identification_number);
                this.telephone().value(fullTaxInvoiceFromDataBase.phone);
                this.toggleInvoiceType(fullTaxInvoiceFromDataBase.invoice_type);
                if(fullTaxInvoiceFromDataBase.invoice_type === 'personal') {
                    this.personalFirstName().value(fullTaxInvoiceFromDataBase.personal_firstname);
                    this.personalLastName().value(fullTaxInvoiceFromDataBase.personal_lastname);
                }else{
                    this.company().value(fullTaxInvoiceFromDataBase.company_name);
                    if(fullTaxInvoiceFromDataBase.branch_office === '1'){
                        this.toggleTypeBranch('branch');
                        this.branchName(fullTaxInvoiceFromDataBase.branch);
                    }else{
                        this.toggleTypeBranch('head');
                    }
                }
            }
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
        },

        isUsePersonal: function (){
            if(!fullTaxInvoiceFromDataBase.is_full_invoice){
                return true;
            }
            return fullTaxInvoiceFromDataBase.invoice_type === 'personal' ?? false ;
        },
        isBranch: function (){
            if(!fullTaxInvoiceFromDataBase.is_full_invoice){
                return false;
            }
            return fullTaxInvoiceFromDataBase.branch_office === '1' ?? false ;
        },
        ignoreAllFields: function (){
            this.personalFirstName().visible(false);
            this.personalFirstName().disabled(true);
            this.personalLastName().visible(false);
            this.personalLastName().disabled(true);
            this.taxIdentificationNumber().visible(false);
            this.telephone().visible(false);
            this.telephone().disabled(true);
            this.company().visible(false);
            this.company().disabled(true);
            this.companyBranch().visible(false);
            this.companyBranch().disabled(true);
        }
    });
});
