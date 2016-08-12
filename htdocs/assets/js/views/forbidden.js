"use strict";
define(function(require) {
    var View = require('view'),
        Templates = require('templates');


    /**
     * The 403 view
     */
    var ForbiddenView = View.extend({
        template: Templates['forbidden'],
        _render: function() {
            this.App.setTitle('403');
            this.$el.html(this.template());

            this.App.hideLoader();
        }
    });

    return ForbiddenView;
});
