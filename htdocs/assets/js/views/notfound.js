"use strict";
define(function(require) {
    var View = require('view'),
        Templates = require('templates');


    /**
     * The 404 view
     */
    var NotFoundView = View.extend({
        template: Templates['notfound'],
        _render: function() {
            this.App.setTitle('404');
            this.$el.html(this.template());

            this.App.hideLoader();
        }
    });

    return NotFoundView;
});
