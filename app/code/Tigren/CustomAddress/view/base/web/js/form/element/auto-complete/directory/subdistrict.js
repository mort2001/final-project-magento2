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
            country: '',
            modules: {
                parent: '${ $.parentName }',
                region: '${ $.parentName }.region_id',
                regionInput: '${ $.parentName }.region',
                city: '${ $.parentName }.city_id',
                cityInput: '${ $.parentName }.city',
                subdistrictInput: '${ $.parentName }.subdistrict',
                postcode: '${ $.parentName }.postcode'
            },
            imports: {
                countryVal: '${$.parentName}.country_id:value'
            }
        },

        additionalClass: 'address-subdistrict-field',

        countryVal: function(country_id) {
            this.country = country_id;
        },

        /**
         * Filtered options list by value from filter options list
         *
         * @param {Array} subdistrictOptions - option list
         * @param {String} value
         *
         * @returns {Array} filters result
         */
        _getFilteredArray: function (subdistrictOptions, value) {
            var subdistrictIndex = 0,
                subdistrictArray = [],
                subdistrictCurOptionLabel,
                subdistrictCurOptionValue,
                subdistrictCurOption,
                addedLabels = [],
                newSubdistrictOptions;

            newSubdistrictOptions = subdistrictOptions.filter(subdistrict => subdistrict.country_id === this.country);

            if (!newSubdistrictOptions) {
                return subdistrictArray;
            }

            for (subdistrictIndex; subdistrictIndex < newSubdistrictOptions.length; subdistrictIndex++) {
                subdistrictCurOptionLabel = newSubdistrictOptions[subdistrictIndex].label;
                subdistrictCurOptionValue = newSubdistrictOptions[subdistrictIndex].value;
                subdistrictCurOption = newSubdistrictOptions[subdistrictIndex].label.toLowerCase();

                if (subdistrictCurOption.indexOf(value) > -1) { /*eslint max-depth: [2, 4]*/
                    var cityOptions = this.city().initialOptions,
                        cityIndex = 0;

                    for (cityIndex; cityIndex < cityOptions.length; cityIndex++) {
                        if (cityOptions[cityIndex].value === newSubdistrictOptions[subdistrictIndex].city_id) {
                            break;
                        }
                    }

                    var regionOptions = this.region().initialOptions,
                        regionIndex = 0;

                    for (regionIndex; regionIndex < regionOptions.length; regionIndex++) {
                        if (regionOptions[regionIndex].value === cityOptions[cityIndex].region_id) {
                            break;
                        }
                    }

                    var newLabel;
                    if (newSubdistrictOptions[subdistrictIndex].zipcode) {
                        newLabel = subdistrictCurOptionLabel + ', ' + cityOptions[cityIndex].label + ', ' +
                            regionOptions[regionIndex].label + ', ' + newSubdistrictOptions[subdistrictIndex].zipcode;
                    } else {
                        newLabel = subdistrictCurOptionLabel + ', ' + cityOptions[cityIndex].label + ', ' +
                            regionOptions[regionIndex].label;
                    }
                    if ($.inArray(newLabel, addedLabels) === -1) {
                        addedLabels.push(newLabel);
                        var newSubdistrictOption = $.extend(true, [], newSubdistrictOptions[subdistrictIndex]);
                        newSubdistrictOption.value = newSubdistrictOptions[subdistrictIndex].value + '-' +
                            cityOptions[cityIndex].value + '-' + regionOptions[regionIndex].value;
                        newSubdistrictOption.label = newLabel;
                        newSubdistrictOption.subdistrict_id = subdistrictCurOptionValue;
                        newSubdistrictOption.subdistrict = subdistrictCurOptionLabel;
                        newSubdistrictOption.city_id = cityOptions[cityIndex].value;
                        newSubdistrictOption.city = cityOptions[cityIndex].label;
                        newSubdistrictOption.region_id = regionOptions[regionIndex].value;
                        newSubdistrictOption.region = regionOptions[regionIndex].label;
                        subdistrictArray.push(newSubdistrictOption);
                    }
                }
            }

            return subdistrictArray;
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

            this.city().cacheOptions.plain = [
                $.extend(true, [], {
                    label: data.city,
                    value: data.city_id
                })];
            this.city().value(data.city_id);
            this.city().setCaption();
            this.cityInput().value(data.city);

            this.cacheOptions.plain = [
                $.extend(true, [], {
                    label: data.subdistrict,
                    value: data.subdistrict_id
                })];
            this.value(data.subdistrict_id);
            this.setCaption();
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
