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

    $.widget('mage.subdistrictUpdater', {
        options: {
            subdistrictTemplate:
                '<option value="<%- data.value %>" <% if (data.isSelected) { %>selected="selected"<% } %>>' +
                '<%- data.title %>' +
                '</option>',
            isSubdistrictRequired: true,
            isCityRequired: true,
            currentCity: null,
            currentSubdistrict: null,
            isMultipleCitiesAllowed: true,
            isMultipleSubdistrictsAllowed: true
        },

        /**
         *
         * @private
         */
        _create: function () {
            this._initCountryElement();
            this._initRegionElement();
            this._initCityElement();
            this._initSubdistrictElement();

            this.currentSubdistrictOption = this.options.currentSubdistrict;
            this.subdistrictTmpl = mageTemplate(this.options.subdistrictTemplate);

            this._updateSubdistrict(this.element.find('option:selected').val());

            $(this.options.subdistrictListId).on('change', $.proxy(function (e) {
                this.setOption = false;
                this.currentSubdistrictOption = $(e.target).val();
                this._updateSubdistrict(this.element.find('option:selected').val());
            }, this));

            $(this.options.subdistrictInputId).on('focusout', $.proxy(function () {
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
                    this._updateSubdistrict(false);
                    this._updateZipcode(false);
                }, this));
            }
        },

        /**
         *
         * @private
         */
        _initRegionElement: function () {
            var regionListElm = $(this.options.regionListId);
            if (regionListElm.length) {
                regionListElm.on('change', $.proxy(function (e) {
                    this.setOption = false;
                    this._updateSubdistrict(false);
                    this._updateZipcode(false);
                }, this));
            }
        },

        /**
         *
         * @private
         */
        _initCityElement: function () {
            if (this.options.isMultipleCitiesAllowed) {
                this.element.parents('div.field').show();
                this.element.on('change', $.proxy(function (e) {
                    this._updateSubdistrict($(e.target).val());
                    this._updateZipcode(false);
                }, this));

            } else {
                this.element.parents('div.field').hide();
            }
        },

        /**
         *
         * @private
         */
        _initSubdistrictElement: function () {
            var subdistrictListElm = $(this.options.subdistrictListId);
            if (this.options.isMultipleSubdistrictsAllowed) {
                subdistrictListElm.parents('div.field').show();
                subdistrictListElm.on('change', $.proxy(function (e) {
                    this._updateZipcode($(e.target).val());
                }, this));

                if (this.options.isSubdistrictRequired) {
                    subdistrictListElm.addClass('required-entry');
                    subdistrictListElm.parents('div.field').addClass('required');
                }
            } else {
                subdistrictListElm.parents('div.field').hide();
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
         * @param {String} key - subdistrict code
         * @param {Object} value - subdistrict object
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

                if (this.options.defaultSubdistrict === key) {
                    tmplData.isSelected = true;
                }

                tmpl = this.subdistrictTmpl({
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
            var args = [
                'clearError',
                this.options.subdistrictListId,
                this.options.subdistrictInputId,
                this.options.postcodeId];

            if (this.options.clearError && typeof this.options.clearError === 'function') {
                this.options.clearError.call(this);
            } else {
                if (!this.options.form) {
                    this.options.form = this.element.closest('form').length ? $(this.element.closest('form')[0]) : null;
                }

                this.options.form = $(this.options.form);

                this.options.form && this.options.form.data('validator') &&
                this.options.form.validation.apply(this.options.form, _.compact(args));

                // Clean up errors on subdistrict fix
                $(this.options.subdistrictInputId).removeClass('mage-error').parent().find('[generated]').remove();
                $(this.options.subdistrictListId).removeClass('mage-error').parent().find('[generated]').remove();
            }
        },

        /**
         * Update dropdown list based on the city selected
         *
         * @param {String|Boolean} city - 2 uppercase letter for city code
         * @private
         */
        _updateSubdistrict: function (city) {
            this.options.currentCity = city;

            // Clear validation error messages
            var subdistrictList = $(this.options.subdistrictListId),
                subdistrictInput = $(this.options.subdistrictInputId),
                label = subdistrictList.parent().siblings('label'),
                requiredLabel = subdistrictList.parents('div.field');

            this._clearError();
            this._checkSubdistrictRequired();

            // Populate subdistrict dropdown list if available or use input box
            if (city && this.options.subdistrictJson[city]) {
                this._removeSelectOptions(subdistrictList);
                $.each(this.options.subdistrictJson[city], $.proxy(function (key, value) {
                    this._renderSelectOption(subdistrictList, key, value);
                }, this));

                // if (this.currentSubdistrictOption) {
                //     subdistrictList.val(this.currentSubdistrictOption);
                // }

                if (this.currentSubdistrictOption) {
                    subdistrictList.val(this.currentSubdistrictOption);
                    subdistrictInput.val();
                    subdistrictList.find('option').filter(function () {
                        if ($(this).attr('selected')) {
                            subdistrictInput.val(this.text);
                        }
                    });
                } else {
                    subdistrictInput.val();
                }

                if (this.setOption) {
                    subdistrictList.find('option').filter(function () {
                        return this.text === subdistrictInput.val();
                    }).attr('selected', true);
                }

                if (this.options.isSubdistrictRequired) {
                    subdistrictList.addClass('required-entry');
                    requiredLabel.addClass('required');
                } else {
                    subdistrictList.removeClass('required-entry validate-select').removeAttr('data-validate');
                    requiredLabel.removeClass('required');
                }

                subdistrictList.show();
                subdistrictInput.hide();
                label.attr('for', subdistrictList.attr('id'));
            } else {
                if (this.options.isSubdistrictRequired && $(subdistrictInput).is(':visible') === false) {
                    subdistrictInput.addClass('required-entry');
                    requiredLabel.addClass('_required');
                    requiredLabel.addClass('required');
                } else {
                    requiredLabel.removeClass('required');
                    requiredLabel.removeClass('_required');
                    subdistrictInput.removeClass('required-entry');
                }

                subdistrictList.removeClass('required-entry').hide().val('');
                subdistrictInput.show();
                label.attr('for', subdistrictInput.attr('id'));
            }

            // Add defaultvalue attribute to subdistrict select element
            subdistrictList.attr('defaultvalue', this.options.defaultSubdistrict);
        },

        /**
         * Update postcode based on the subdistrict selected
         *
         * @param {String|Boolean} subdistrict - 2 uppercase letter for subdistrict code
         * @private
         */
        _updateZipcode: function (subdistrict) {
            var postcodeInputIdElm = $(this.options.postcodeId);
            if (subdistrict
                && postcodeInputIdElm.length
                && this.options.currentCity
                && this.options.subdistrictJson[this.options.currentCity]
                && this.options.subdistrictJson[this.options.currentCity][subdistrict]
                && this.options.subdistrictJson[this.options.currentCity][subdistrict].zipcode
            ) {
                postcodeInputIdElm.val(this.options.subdistrictJson[this.options.currentCity][subdistrict].zipcode);
            } else {
                postcodeInputIdElm.val('');
            }
        },

        _checkSubdistrictRequired: function () {
            var length = $(this.options.subdistrictListId + ' > option').length;
            this.options.isSubdistrictRequired = true;
            if (length <= 1) {
                this.options.isSubdistrictRequired = false;
            }
        }
    });

    return $.mage.subdistrictUpdater;
});
