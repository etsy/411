"use strict";
define(function(require) {
    var View = require('view'),
        NavbarView = require('views/navbar'),
        Templates = require('templates'),
        Config = require('config'),
        Util = require('util');


    /**
     * The health View
     */
    var HealthView = View.extend({
        template: Templates['health'],
        _load: function() {
            // Retrieve config.
            this.App.ajax({
                url: Config.api_root + 'health',
                success: this.cbLoaded(function(resp) {
                    this.data = resp;
                    this.render();
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        },
        _render: function() {
            this.App.setTitle('Health');

            this.$el.append(this.template(this.data));

            this.App.hideLoader();
        }
    });

    return HealthView;
});
