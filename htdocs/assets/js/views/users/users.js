"use strict";
define(function(require) {
    var View = require('view'),
        NavbarView = require('views/navbar'),
        TableView = require('views/table'),
        Templates = require('templates');


    var UsersNavbarView = NavbarView.extend({
        title: 'Users',
        sidelinks: [
            {name: 'Create', icon: 'file', link: '/users/new', action: 'create'}
        ]
    });

    var UserEntryView = View.extend({
        tagName: 'tr',
        template: Templates['users/userentry'],
        _render: function() {
            var vars = this.model.toJSON();
            this.$el.html(this.template(vars));
        },
        selectAction: function(action) {
            switch(action) {
                case 'open': this.open(); break;
            }
        },
        open: function() {
            this.$('a')[0].click();
        }
    });

    var UsersTableView = TableView.extend({
        subView: UserEntryView,
        selectable: true,
        columns: [
            {name: '', sorter: 'false'},
            {name: 'Name', width: 50},
            {name: 'Email', width: 40},
            {name: 'Admin', sorter: 'bool'},
        ]
    });

    /**
     * The users View
     */
    var UsersView = View.extend({
        _render: function() {
            this.App.setTitle('Users');
            this.registerView(new UsersNavbarView(this.App), true);
            this.registerView(new UsersTableView(
                this.App, {collection: this.App.Data.Users}
            ), true);

            this.App.hideLoader();
        }
    });

    return UsersView;
});
