"use strict";
define(function(require) {
    var _ = require('underscore'),
        Handlebars = require('handlebars'),
        TableRenderer = require('views/renderer/table');


    var MAC_RE = /^([\dA-F]{2}[:-]){5}([\dA-F]{2})$/i;

    /**
     * Extracts links and makes them clickable.
     */
    var MACRenderer = TableRenderer.extend({
        auto: true,
        remote: 'mac',
        match: function(key, val) {
            return MAC_RE.test(val);
        },
        render: function(key, val, data) {
            if(_.isObject(data)) {
                return TableRenderer.tabulate(data);
            }
            return Handlebars.Utils.escapeExpression(val);
        }
    });

    return MACRenderer;
});
