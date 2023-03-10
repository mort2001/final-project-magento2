/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'mage/template',
    'underscore',
    'jquery/ui',
    'mage/validation'
], function ($, mageTemplate, _) {
    'use strict';

    $.widget('mage.regionUpdater', {
        options: {
            regionTemplate:
                '<option value="<%- data.value %>" <% if (data.isSelected) { %>selected="selected"<% } %>>' +
                '<%- data.title %>' +
                '</option>',
            isRegionRequired: true,
            isZipRequired: true,
            isCountryRequired: true,
            currentRegion: null,
            isMultipleCountriesAllowed: true
        },

        /**
         *
         * @private
         */
        _create: function () {
            this._initCountryElement();

            this.currentRegionOption = this.options.currentRegion;
            this.regionTmpl = mageTemplate(this.options.regionTemplate);

            this._updateRegion(this.element.find('option:selected').val());

            $(this.options.regionListId).on('change', $.proxy(function (e) {
                this.setOption = false;
                this.currentRegionOption = $(e.target).val();
                this._updateRegion(this.element.find('option:selected').val());
            }, this));

            $(this.options.regionInputId).on('focusout', $.proxy(function () {
                this.setOption = true;
            }, this));

            $(this.options.postcodeId).on('keydown', $.proxy(function (e) {
                // Allow: backspace, delete, tab, escape, enter and .
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                    // Allow: Ctrl/cmd+A
                    (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                    // Allow: Ctrl/cmd+C
                    (e.keyCode === 67 && (e.ctrlKey === true || e.metaKey === true)) ||
                    // Allow: Ctrl/cmd+X
                    (e.keyCode === 88 && (e.ctrlKey === true || e.metaKey === true)) ||
                    // Allow: home, end, left, right
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    // let it happen, don't do anything
                    return true;
                }

                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    return false;
                }

                var numberToString = $(e.currentTarget).val();
                if (numberToString.length >= 5) {
                    return false;
                }

                return true;
            }, this));
        },

        /**
         *
         * @private
         */
        _initCountryElement: function () {

            if (this.options.isMultipleCountriesAllowed) {
                this.element.parents('div.field').show();
                this.element.on('change', $.proxy(function (e) {
                    this._updateRegion($(e.target).val());
                }, this));

                if (this.options.isCountryRequired) {
                    this.element.addClass('required-entry');
                    this.element.parents('div.field').addClass('required');
                }
            } else {
                this.element.parents('div.field').hide();
            }
        },

        /**
         * Remove options from dropdown list
         *
         * @param {Object} selectElement - jQuery object for dropdown list
         * @private
         */
        _removeSelectOptions: function (selectElement) {
            selectElement.find('option').each(function (index) {
                if (index) {
                    $(this).remove();
                }
            });
        },

        /**
         * Render dropdown list
         * @param {Object} selectElement - jQuery object for dropdown list
         * @param {String} key - region code
         * @param {Object} value - region object
         * @private
         */
        _renderSelectOption: function (selectElement, key, value) {
            selectElement.append($.proxy(function () {
                var name = value.name.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&'),
                    tmplData,
                    tmpl;

                if (value.code && $(name).is('span')) {
                    key = value.code;
                    value.name = $(name).text();
                }

                tmplData = {
                    value: key,
                    title: value.name,
                    isSelected: false
                };

                if (this.options.defaultRegion === key) {
                    tmplData.isSelected = true;
                }

                tmpl = this.regionTmpl({
                    data: tmplData
                });

                return $(tmpl);
            }, this));
        },

        /**
         * Takes clearError callback function as first option
         * If no form is passed as option, look up the closest form and call clearError method.
         * @private
         */
        _clearError: function () {
            var args = ['clearError', this.options.regionListId, this.options.regionInputId, this.options.postcodeId];

            if (this.options.clearError && typeof this.options.clearError === 'function') {
                this.options.clearError.call(this);
            } else {
                if (!this.options.form) {
                    this.options.form = this.element.closest('form').length ? $(this.element.closest('form')[0]) : null;
                }

                this.options.form = $(this.options.form);

                this.options.form && this.options.form.data('validator') &&
                this.options.form.validation.apply(this.options.form, _.compact(args));

                // Clean up errors on region & zip fix
                $(this.options.regionInputId).removeClass('mage-error').parent().find('[generated]').remove();
                $(this.options.regionListId).removeClass('mage-error').parent().find('[generated]').remove();
                $(this.options.postcodeId).removeClass('mage-error').parent().find('[generated]').remove();
            }
        },

        /**
         * Update dropdown list based on the country selected
         *
         * @param {String} country - 2 uppercase letter for country code
         * @private
         */
        _updateRegion: function (country) {
            // Clear validation error messages
            var regionList = $(this.options.regionListId),
                regionInput = $(this.options.regionInputId),
                postcode = $(this.options.postcodeId),
                label = regionList.parent().siblings('label'),
                requiredLabel = regionList.parents('div.field');

            this._clearError();
            this._checkRegionRequired(country);

            // Populate state/province dropdown list if available or use input box
            if (this.options.regionJson[country]) {
                var options = [];
                _.each(this.options.regionJson[country], function (country, key) {
                    var option = {
                        value: {
                            code: country.code,
                            name: country.name
                        },
                        key: key

                    };
                    options.push(option);
                }, this);
                options.sort(function (a, b) {
                    if (a.value.name.toLowerCase() > b.value.name.toLowerCase()) {
                        return 1;
                    }
                    if (a.value.name.toLowerCase() < b.value.name.toLowerCase()) {
                        return -1;
                    }
                    return 0;
                });
                this._removeSelectOptions(regionList);
                $.each(options, $.proxy(function (key, region) {
                    this._renderSelectOption(regionList, region.key, region.value);
                }, this));

                if (this.currentRegionOption) {
                    regionList.val(this.currentRegionOption);
                    regionInput.val();
                    regionList.find('option').filter(function () {
                        if ($(this).attr('selected')) {
                            regionInput.val(this.text);
                        }
                    });
                } else {
                    regionInput.val();
                }

                if (this.setOption) {
                    regionList.find('option').filter(function () {
                        return this.text === regionInput.val();
                    }).attr('selected', true);
                }

                if (this.options.isRegionRequired) {
                    regionList.addClass('required-entry').removeAttr('disabled');
                    requiredLabel.addClass('required');
                } else {
                    regionList.removeClass('required-entry validate-select').removeAttr('data-validate');
                    requiredLabel.removeClass('required');

                    if (!this.options.optionalRegionAllowed) { //eslint-disable-line max-depth
                        regionList.attr('disabled', 'disabled');
                    } else {
                        regionList.prop('disabled', false); //
                    }
                }

                if (this.options.type && 'order-shipping_address' == this.options.type) {
                    var sameAsBillingCheckbox = $('#' + this.options.type).find('#order-shipping_same_as_billing');
                    if (sameAsBillingCheckbox.is(':checked')) {
                        regionList.attr('disabled', 'disabled');
                    }
                }

                regionList.show();
                regionInput.hide();
                label.attr('for', regionList.attr('id'));
            } else {
                if (this.options.isRegionRequired) {
                    regionInput.addClass('required-entry').removeAttr('disabled');
                    requiredLabel.addClass('required');
                } else {
                    if (!this.options.optionalRegionAllowed) { //eslint-disable-line max-depth
                        regionInput.attr('disabled', 'disabled');
                    }
                    requiredLabel.removeClass('required');
                    regionInput.removeClass('required-entry');
                }

                if (this.options.type && 'order-shipping_address' == this.options.type) {
                    var sameAsBillingCheckbox = $('#' + this.options.type).find('#order-shipping_same_as_billing');
                    if (sameAsBillingCheckbox.is(':checked')) {
                        regionInput.attr('disabled', 'disabled');
                    }
                }

                regionList.removeClass('required-entry').prop('disabled', 'disabled').hide();
                regionInput.show();
                label.attr('for', regionInput.attr('id'));
            }

            // If country is in optionalzip list, make postcode input not required
            if (this.options.isZipRequired) {
                $.inArray(country, this.options.countriesWithOptionalZip) >= 0 ?
                    postcode.removeClass('required-entry').closest('.field').removeClass('required') :
                    postcode.addClass('required-entry').closest('.field').addClass('required');
            }

            // Add defaultvalue attribute to state/province select element
            regionList.attr('defaultvalue', this.options.defaultRegion);
        },

        /**
         * Check if the selected country has a mandatory region selection
         *
         * @param {String} country - Code of the country - 2 uppercase letter for country code
         * @private
         */
        _checkRegionRequired: function (country) {
            var self = this;

            this.options.isRegionRequired = false;
            $.each(this.options.regionJson.config['regions_required'], function (index, elem) {
                if (elem === country) {
                    self.options.isRegionRequired = true;
                }
            });
        }
    });

    $.validator.addMethod(
        'aureatelabsvalidationrule',
        function (value, element) {
            //Perform your operation here and return the result true/false.
            return true / false;
        },
        $.mage.__('Your validation message.')
    );

    return $.mage.regionUpdater;
});
