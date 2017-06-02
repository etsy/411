"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        NavbarView = require('views/navbar'),
        Templates = require('templates'),
        Config = require('config'),
        Util = require('util'),
        Moment = require('moment');


    var AdminNavbarView = NavbarView.extend({
        title: 'Admin',
    });

    /**
     * The admin View
     */
    var AdminView = View.extend({
        template: Templates['admin'],
        events: {
            'click #save-button': 'saveSettings'
        },
        _load: function() {
            // Retrieve config.
            this.App.ajax({
                url: Config.api_root + 'admin',
                success: this.cbLoaded(function(resp) {
                    this.data = resp;
                    this.data['timezones'] = Moment.tz.names();

                    this.render();
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        },
        _render: function() {
            this.App.setTitle('Admin');
            this.registerView(new AdminNavbarView(this.App), true);

            this.$el.append(this.template(this.data));

            Util.autosize(this.registerElement('textarea[name=announcement]'));

            this.App.hideLoader();
        },
        saveSettings: function() {
            this.App.showLoader();

            var form = this.$('#admin-form');
            var data = Util.serializeForm(form);

            this.App.ajax({
                url: Config.api_root + 'admin',
                method: 'post',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify(data),
                success: this.cbRendered(function(resp) {
                    this.App.addMessage('Update successful', 2);
                    this.data = resp;
                }),
                complete: $.proxy(function() {
                    this.App.hideLoader();
                }, this)
            });
            return false;
        }
    });

    return AdminView;
});
