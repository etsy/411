"use strict";
define(function(require) {
    var View = require('view'),
        Templates = require('templates'),
        Config = require('config'),
        Data = require('data');


    /**
     * The logout View
     */
    var LogoutView = View.extend({
        template: Templates['logout'],
        _render: function() {
            this.App.setTitle('Logout');
            this.$el.html(this.template());

            // Logout the user.
            this.App.ajax({
                url: Config.api_root + 'logout',
                method: 'post',
                contentType: 'application/json; charset=utf-8',
                complete: $.proxy(function() {
                    requirejs.undef('text!data_json');
                    require(['text!data_json'], $.proxy(function(d) {
                        Data.reload(d);
                        this.App.refresh();
                    }, this));
                }, this)
            });

            this.App.hideLoader();
        }
    });

    return LogoutView;
});
