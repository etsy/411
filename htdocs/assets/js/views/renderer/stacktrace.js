"use strict";
define(function(require) {
    var Renderer = require('views/renderer'),
        Handlebars = require('handlebars');


    /**
     * Render a stack trace.
     */
    var StacktraceRenderer = Renderer.extend({
        auto: false,
        render: function(key, val, data) {
            return '<pre>' + Handlebars.Utils.escapeExpression(val.replace(/\\\\n/g, '\n').replace(/\\n/g, '\n')) + '</pre>';
        },
    });

    return StacktraceRenderer;
});
