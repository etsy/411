"use strict";
define(function(require) {
    var View = require('view'),
        Templates = require('templates'),
        Config = require('config'),
        Util = require('util'),
        URI = require('uri'),
        Data = require('data'),

        User = require('models/user');


    /**
     * The login view.
     */
    var LoginView = View.extend({
        template: Templates['login'],
        events: {
            'click #login-button': 'login'
        },
        _render: function() {
            this.App.setTitle('Login');
            this.$el.html(this.template());
            this.$('input[name=name]').focus();

            this.App.hideLoader();
        },
        login: function() {
            this.App.showLoader();

            var data = Util.serializeForm(this.$('#login-form'));

            // Authenticate the user.
            this.App.ajax({
                url: Config.api_root + 'login',
                data: JSON.stringify(data),
                method: 'post',
                contentType: 'application/json; charset=utf-8',
                success: this.cbRendered(function(id) {
                    if(!id) {
                        this.App.addMessage('Invalid credentials');
                    } else {
                        requirejs.undef('text!data_json');
                        require(['text!data_json'], $.proxy(function(d) {
                            Data.reload(d);
                            this.App.refresh();

                            var link = new URI(window.location.href);
                            var redirect = link.query(true)['redirect'] || '';

                            this.App.Router.navigate(redirect, {trigger:true});
                        }, this));
                    }
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
            return false;
        }
    });

    return LoginView;
});
