"use strict";
define(function(require) {
    var Handlebars = require('handlebars'),
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
            return [
                '<strong>',
                Handlebars.Utils.escapeExpression(val),
                '</strong>',
                TableRenderer.tabulate(data)
            ].join('');
        }
    });

    return MACRenderer;
});
