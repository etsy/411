"use strict";
define(function(require) {
    var TableRenderer = require('views/renderer/table'),
        Handlebars = require('handlebars');


    var IP_RE = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

    /**
     * Extracts links and makes them clickable.
     */
    var IPRenderer = TableRenderer.extend({
        auto: true,
        remote: 'ip',
        match: function(key, val) {
            return IP_RE.test(val);
        },
        preview: function(key, val) {
            var url = 'https://freegeoip.net/?q=' + val;
            return [
                '<a target="_blank" rel="noreferrer" href="',
                Handlebars.Utils.escapeExpression(url),
                '">',
                Handlebars.Utils.escapeExpression(val),
                '</a>'
            ].join('');
        },
        render: function(key, val, data) {
            return TableRenderer.tabulate(data);
        }
    });

    return IPRenderer;
});
