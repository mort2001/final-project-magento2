/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'Tigren_CustomAddress/js/form/element/auto-complete/directory-ui-select',
    'jquery'
], function (
    Select,
    $
) {
    'use strict';

    return Select.extend({
        defaults: {
            modules: {
                regionInput: '${ $.parentName }.region',
                city: '${ $.parentName }.city_id',
                cityInput: '${ $.parentName }.city',
                subdistrict: '${ $.parentName }.subdistrict_id',
                subdistrictInput: '${ $.parentName }.subdistrict',
                postcode: '${ $.parentName }.postcode'
            }
        },

        additionalClass: 'address-region-field',

        /**
         * Filtered options list by value from filter options list
         *
         * @param {Array} regionOptions - option list
         * @param {String} value
         *
         * @returns {Array} filters result
         */
        _getFilteredArray: function (regionOptions, value) {
            var regionIndex = 0,
                regionArray = [],
                regionCurOptionLabel,
                regionCurOptionValue,
                regionCurOption,
                addedLabels = [];

            if (!regionOptions) {
                return regionArray;
            }

            for (regionIndex; regionIndex < regionOptions.length; regionIndex++) {
                regionCurOptionLabel = regionOptions[regionIndex].label;
                regionCurOptionValue = regionOptions[regionIndex].value;
                regionCurOption = regionOptions[regionIndex].label.toLowerCase();

                if (regionCurOption.indexOf(value) > -1) { /*eslint max-depth: [2, 4]*/
                    var cityOptions = this.city().initialOptions,
                        cityIndex = 0;

                    for (cityIndex; cityIndex < cityOptions.length; cityIndex++) {
                        if (cityOptions[cityIndex].region_id === regionOptions[regionIndex].value) {
                            var subdistrictOptions = this.subdistrict().initialOptions,
                                subdistrictIndex = 0;

                            for (subdistrictIndex; subdistrictIndex < subdistrictOptions.length; subdistrictIndex++) {
                                if (subdistrictOptions[subdistrictIndex].city_id === cityOptions[cityIndex].value) {
                                    var newLabel;
                                    if (subdistrictOptions[subdistrictIndex].zipcode) {
                                        newLabel = subdistrictOptions[subdistrictIndex].label + ', ' +
                                            cityOptions[cityIndex].label + ', ' + regionCurOptionLabel + ', ' +
                                            subdistrictOptions[subdistrictIndex].zipcode;
                                    } else {
                                        newLabel = subdistrictOptions[subdistrictIndex].label + ', ' +
                                            cityOptions[cityIndex].label + ', ' + regionCurOptionLabel;
                                    }
                                    if ($.inArray(newLabel, addedLabels) === -1) {
                                        addedLabels.push(newLabel);
                                        var newReionOption = $.extend(true, [], regionOptions[regionIndex]);
                                        newReionOption.value = subdistrictOptions[subdistrictIndex].value + '-' +
                                            cityOptions[cityIndex].value + '-' + regionCurOptionValue;
                                        newReionOption.label = newLabel;
                                        newReionOption.zipcode = subdistrictOptions[subdistrictIndex].zipcode
                                            ? subdistrictOptions[subdistrictIndex].zipcode
                                            : '';
                                        newReionOption.subdistrict_id = subdistrictOptions[subdistrictIndex].value;
                                        newReionOption.subdistrict = subdistrictOptions[subdistrictIndex].label;
                                        newReionOption.city_id = cityOptions[cityIndex].value;
                                        newReionOption.city = cityOptions[cityIndex].label;
                                        newReionOption.region_id = regionCurOptionValue;
                                        newReionOption.region = regionCurOptionLabel;
                                        regionArray.push(newReionOption);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return regionArray;
        },

        /**
         * Toggle activity list element
         *
         * @param {Object} data - selected option data
         * @returns {Object} Chainable
         */
        toggleOptionSelected: function (data) {
            if (this.lastSelectable && data.hasOwnProperty(this.separator)) {
                return this;
            }

            this.cacheOptions.plain = [
                $.extend(true, [], {
                    label: data.region,
                    value: data.region_id
                })];
            this.value(data.region_id);
            this.setCaption();
            this.regionInput().value(data.region);

            this.city().cacheOptions.plain = [
                $.extend(true, [], {
                    label: data.city,
                    value: data.city_id
                })];
            this.city().value(data.city_id);
            this.city().setCaption();
            this.cityInput().value(data.city);

            this.subdistrict().cacheOptions.plain = [
                $.extend(true, [], {
                    label: data.subdistrict,
                    value: data.subdistrict_id
                })];
            this.subdistrict().value(data.subdistrict_id);
            this.subdistrict().setCaption();
            this.subdistrictInput().value(data.subdistrict);

            this.postcode().cacheOptions.plain = [
                $.extend(true, [], {
                    label: data.zipcode,
                    value: data.zipcode
                })];
            this.postcode().value(data.zipcode);
            this.postcode().setCaption();

            this.listVisible(false);

            return this;
        }
    });
});
