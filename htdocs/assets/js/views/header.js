"use strict";
define(function(require) {
    var View = require('view'),
        Templates = require('templates'),
        Data = require('data');


    /**
     * The header nav View
     */
    var HeaderView = View.extend({
        template: Templates['header'],
        events: {
            'click .shortcuts-button': 'showHelp',
            'click .messages-button': 'showMessages'
        },
        showHelp: function() {
            this.App.showHelp();
        },
        showMessages: function() {
            this.App.showMessages();
        },
        _render: function() {
            var sidelinks = [
                {name: 'Keyboard shortcuts', action: 'shortcuts'},
                {name: 'Message history', action: 'messages'}
            ];
            var user = this.App.Data.User;
            var vars = {
                app_name: Data.AppName,
            };

            // If the user is logged in, populate the menu with some additional items.
            if(user) {
                sidelinks.push({name: 'Health', link: '/health'});
                //sidelinks.push({name: 'Settings', link: '/settings'});
                if(user.get('admin')) {
                    sidelinks.push({name: 'Admin', link: '/admin'});
                }
                sidelinks.push({divider: true});
                sidelinks.push({name: 'User Settings', link: '/user/' + user.id});
                sidelinks.push({name: 'Logout', link: '/logout'});

                vars.name = user.get('real_name');
                // Rerender the header if the user is updated!
                this.listenTo(user, 'change', this.rerender);
            } else {
                sidelinks.push({divider: true});
                sidelinks.push({name: 'Login', link: '/login'});
            }

            vars.sidelinks = sidelinks;
            vars.links = [
                {name: 'Alerts', link: '/alerts'},
                {name: 'Searches', link: '/searches'},
                {name: 'Groups', link: '/groups'},
                {name: 'Users', link: '/users'},
                // {name: 'Reports', link: '/reports'},
                {name: 'Lists', link: '/lists'},
            ];
            this.$el.html(this.template(vars));

            this.App.registerKbdShortcut('alt+`', $.proxy(this.click, this, '.navbar-brand'), 'Go to Homepage', true);
            for(var i = 0; i < vars.links.length; ++i) {
                this.App.registerKbdShortcut('alt+' + (i+1), $.proxy(this.click, this, '.navbar-link-' + i), 'Go to ' + vars.links[i].name, true);
            }
        },
        click: function(sel) {
            this.$(sel).click();
        },
    });

    return HeaderView;
});
