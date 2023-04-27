/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define(['jquery'], function ($) {
    'use strict';

    return {
        method: 'rest',
        version: 'V1',
        serviceUrl: ':method/:version',

        /**
         * @param {String} url
         * @param {Object} params
         * @return {*}
         */
        createUrl: function (url, params) {
            var completeUrl = this.serviceUrl + url;
            return this.bindParams(completeUrl, params);
        },

        /**
         * @param {String} url
         * @param {Object} params
         * @return {*}
         */
        bindParams: function (url, params) {
            var urlParts;
            params.method = this.method;
            params.version = this.version;
            urlParts = url.split('/');
            urlParts = urlParts.filter(Boolean);
            $.each(urlParts, function (key, part) {
                part = part.replace(':', '');

                if (params[part] != undefined) { //eslint-disable-line eqeqeq
                    urlParts[key] = params[part];
                }
            });
            return urlParts.join('/');
        }
    };
});