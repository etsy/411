"use strict";
define(function(require) {
    var View = require('view'),
        Templates = require('templates'),
        Config = require('config');


    /**
     * The footer View
     */
    var FooterView = View.extend({
        template: Templates['footer'],

        _render: function() {
            var vars = {
                asset_root: Config.asset_root
            };
            this.$el.html(this.template(vars));
        }
    });

    return FooterView;
});
