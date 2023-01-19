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

    $.widget('mage.cityUpdater', {
        options: {
            cityTemplate:
                '<option value="<%- data.value %>" <% if (data.isSelected) { %>selected="selected"<% } %>>' +
                '<%- data.title %>' +
                '</option>',
            isCityRequired: true,
            isRegionRequired: true,
            currentCity: null,
            isMultipleRegionsAllowed: true
        },

        /**
         *
         * @private
         */
        _create: function () {
            this._initCountryElement();
            this._initRegionElement();

            this.currentCityOption = this.options.currentCity;
            this.cityTmpl = mageTemplate(this.options.cityTemplate);

            this._updateCity(this.element.find('option:selected').val());

            $(this.options.cityListId).on('change', $.proxy(function (e) {
                this.setOption = false;
                this.currentCityOption = $(e.target).val();
                // this._updateCity($(e.target).val());
                this._updateCity(this.element.find('option:selected').val());
            }, this));

            $(this.options.cityInputId).on('focusout', $.proxy(function () {
                this.setOption = true;
            }, this));
        },

        /**
         *
         * @private
         */
        _initCountryElement: function () {
            var countryElm = $(this.options.countryId);
            if (countryElm.length) {
                countryElm.on('change', $.proxy(function (e) {
                    this.setOption = false;
                    this._updateCity(false);
                }, this));
            }
        },

        /**
         *
         * @private
         */
        _initRegionElement: function () {
            if (this.options.isMultipleRegionsAllowed) {
                this.element.parents('div.field').show();
                this.element.on('change', $.proxy(function (e) {
                    this._updateCity($(e.target).val());
                }, this));

                if (this.options.isRegionRequired) {
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
         * @param {String} key - city code
         * @param {Object} value - city object
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

                if (this.options.defaultCity === key) {
                    tmplData.isSelected = true;
                }

                tmpl = this.cityTmpl({
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
            var args = ['clearError', this.options.cityListId, this.options.cityInputId, this.options.postcodeId];

            if (this.options.clearError && typeof this.options.clearError === 'function') {
                this.options.clearError.call(this);
            } else {
                if (!this.options.form) {
                    this.options.form = this.element.closest('form').length ? $(this.element.closest('form')[0]) : null;
                }

                this.options.form = $(this.options.form);

                this.options.form && this.options.form.data('validator') &&
                this.options.form.validation.apply(this.options.form, _.compact(args));

                // Clean up errors on city fix
                $(this.options.cityInputId).removeClass('mage-error').parent().find('[generated]').remove();
                $(this.options.cityListId).removeClass('mage-error').parent().find('[generated]').remove();
            }
        },

        /**
         * Update dropdown list based on the region selected
         *
         * @param {String|Boolean} region - 2 uppercase letter for region code
         * @private
         */
        _updateCity: function (region) {
            // Clear validation error messages
            var cityList = $(this.options.cityListId),
                cityInput = $(this.options.cityInputId),
                label = cityList.parent().siblings('label'),
                requiredLabel = cityList.parents('div.field');

            this._clearError();
            this._checkCityRequired();

            // Populate city dropdown list if available or use input box
            if (region && this.options.cityJson[region]) {
                this._removeSelectOptions(cityList);
                $.each(this.options.cityJson[region], $.proxy(function (key, value) {
                    this._renderSelectOption(cityList, key, value);
                }, this));

                if (this.currentCityOption) {
                    cityList.val(this.currentCityOption);
                    cityInput.val();
                    cityList.find('option').filter(function () {
                        if ($(this).attr('selected')) {
                            cityInput.val(this.text);
                        }
                    });
                } else {
                    cityInput.val();
                }

                if (this.setOption) {
                    cityList.find('option').filter(function () {
                        return this.text === cityInput.val();
                    }).attr('selected', true);
                }

                if (this.options.isCityRequired) {
                    cityList.addClass('required-entry');
                    requiredLabel.addClass('required');
                } else {
                    cityList.removeClass('required-entry validate-select').removeAttr('data-validate');
                    requiredLabel.removeClass('required');
                }

                cityList.show();
                cityInput.hide();
                label.attr('for', cityList.attr('id'));
            } else {
                if (this.options.isCityRequired) {
                    cityInput.addClass('required-entry');
                    requiredLabel.addClass('required');
                } else {
                    requiredLabel.removeClass('required');
                    cityInput.removeClass('required-entry');
                }

                cityList.removeClass('required-entry').hide().val('');
                cityInput.show();
                label.attr('for', cityInput.attr('id'));
            }

            // Add defaultvalue attribute to city select element
            cityList.attr('defaultvalue', this.options.defaultCity);
        },

        _checkCityRequired: function () {
            var length = $(this.options.cityListId + ' > option').length;
            this.options.isCityRequired = true;
            if (length <= 1) {
                this.options.isCityRequired = false;
            }
        }
    });

    return $.mage.cityUpdater;
});
