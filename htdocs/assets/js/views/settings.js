"use strict";
define(function(require) {
    var View = require('view'),
        NavbarView = require('views/navbar'),
        Templates = require('templates'),
        Config = require('config'),
        Util = require('util');


    var SettingsNavbarView = NavbarView.extend({
        title: 'Settings',
    });

    /**
     * The settings View
     */
    var SettingsView = View.extend({
        template: Templates['settings'],
        events: {
            'click #save-button': 'saveSettings'
        },
        _render: function() {
            this.App.setTitle('Settings');
            this.registerView(new SettingsNavbarView(this.App), true);

            this.$el.append(this.template());

            this.App.hideLoader();
        },
        saveSettings: function() {
            this.App.showLoader();

            return false;
        }
    });

    return SettingsView;
});
