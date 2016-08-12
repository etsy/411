"use strict";
define(function(require) {
    var Renderer = require('views/renderer'),
        URI = require('uri'),
        Handlebars = require('handlebars');


    var LINK_RE = /(https?:\/\/[^\s]+)/;

    /**
     * Extracts links and makes them clickable.
     */
    var render = function(key, val) {
        return URI.withinString(val, function(url) {
            return [
                '<a target="_blank" rel="noreferrer" href="',
                Handlebars.Utils.escapeExpression(url),
                '">',
                Handlebars.Utils.escapeExpression(url),
                '</a>'
            ].join('');
        });
    };
    var LinkRenderer = Renderer.extend({
        auto: true,
        match: function(key, val) {
            return LINK_RE.test(val);
        },
        preview: render,
        render: render
    });

    return LinkRenderer;
});
