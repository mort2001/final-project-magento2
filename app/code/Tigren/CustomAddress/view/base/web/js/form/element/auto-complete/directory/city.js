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
                region: '${ $.parentName }.region_id',
                regionInput: '${ $.parentName }.region',
                cityInput: '${ $.parentName }.city',
                subdistrict: '${ $.parentName }.subdistrict_id',
                subdistrictInput: '${ $.parentName }.subdistrict',
                postcode: '${ $.parentName }.postcode'
            }
        },

        additionalClass: 'address-city-field',

        /**
         * Filtered options list by value from filter options list
         *
         * @param {Array} cityOptions - option list
         * @param {String} value
         *
         * @returns {Array} filters result
         */
        _getFilteredArray: function (cityOptions, value) {
            var cityIndex = 0,
                cityArray = [],
                cityCurOptionLabel,
                cityCurOptionValue,
                cityCurOption,
                addedLabels = [];

            if (!cityOptions) {
                return cityArray;
            }

            for (cityIndex; cityIndex < cityOptions.length; cityIndex++) {
                cityCurOptionLabel = cityOptions[cityIndex].label;
                cityCurOptionValue = cityOptions[cityIndex].value;
                cityCurOption = cityOptions[cityIndex].label.toLowerCase();

                if (cityCurOption.indexOf(value) > -1) { /*eslint max-depth: [2, 4]*/
                    var regionOptions = this.region().initialOptions,
                        regionIndex = 0;

                    for (regionIndex; regionIndex < regionOptions.length; regionIndex++) {
                        if (regionOptions[regionIndex].value === cityOptions[cityIndex].region_id) {
                            break;
                        }
                    }

                    var subdistrictOptions = this.subdistrict().initialOptions,
                        subdistrictIndex = 0;

                    for (subdistrictIndex; subdistrictIndex < subdistrictOptions.length; subdistrictIndex++) {
                        if (subdistrictOptions[subdistrictIndex].city_id === cityOptions[cityIndex].value) {
                            var newLabel;
                            if (subdistrictOptions[subdistrictIndex].zipcode) {
                                newLabel = subdistrictOptions[subdistrictIndex].label + ', ' + cityCurOptionLabel +
                                    ', ' + regionOptions[regionIndex].label + ', ' +
                                    subdistrictOptions[subdistrictIndex].zipcode;
                            } else {
                                newLabel = subdistrictOptions[subdistrictIndex].label + ', ' + cityCurOptionLabel +
                                    ', ' + regionOptions[regionIndex].label;
                            }
                            if ($.inArray(newLabel, addedLabels) === -1) {
                                addedLabels.push(newLabel);
                                var newCityOption = $.extend(true, [], cityOptions[cityIndex]);
                                newCityOption.value = subdistrictOptions[subdistrictIndex].value + '-' +
                                    cityCurOptionValue + '-' + regionOptions[regionIndex].value;
                                newCityOption.label = newLabel;
                                newCityOption.zipcode = subdistrictOptions[subdistrictIndex].zipcode
                                    ? subdistrictOptions[subdistrictIndex].zipcode
                                    : '';
                                newCityOption.subdistrict_id = subdistrictOptions[subdistrictIndex].value;
                                newCityOption.subdistrict = subdistrictOptions[subdistrictIndex].label;
                                newCityOption.region_id = regionOptions[regionIndex].value;
                                newCityOption.region = regionOptions[regionIndex].label;
                                newCityOption.city_id = cityCurOptionValue;
                                newCityOption.city = cityCurOptionLabel;
                                cityArray.push(newCityOption);
                            }
                        }
                    }
                }
            }

            return cityArray;
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

            this.region().cacheOptions.plain = [
                $.extend(true, [], {
                    label: data.region,
                    value: data.region_id
                })];
            this.region().value(data.region_id);
            this.region().setCaption();
            this.regionInput().value(data.region);

            this.cacheOptions.plain = [
                $.extend(true, [], {
                    label: data.city,
                    value: data.city_id
                })];
            this.value(data.city_id);
            this.setCaption();
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
