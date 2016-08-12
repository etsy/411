"use strict";
define(function(require) {
    var Renderer = require('views/renderer'),
        Templates = require('templates');


    /**
     * Provides functionality to render data in a table.
     */
    var TableRenderer = Renderer.extend({
        tabulate: function(data) {
            return Templates['renderer/table']({data: data});
        }
    });

    return TableRenderer;
});
