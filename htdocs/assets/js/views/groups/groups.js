"use strict";
define(function(require) {
    var View = require('view'),
        NavbarView = require('views/navbar'),
        TableView = require('views/table'),
        Templates = require('templates'),

        Group = require('models/group');


    var GroupsNavbarView = NavbarView.extend({
        title: 'Groups',
        sidelinks: [
            {name: 'Create', icon: 'file', link: '/groups/new', action: 'create'}
        ]
    });

    var GroupEntryView = View.extend({
        tagName: 'tr',
        template: Templates['groups/groupentry'],
        _render: function() {
            var vars = this.model.toJSON();
            vars.types = Group.Data().Types;
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

    var GroupsTableView = TableView.extend({
        subView: GroupEntryView,
        selectable: true,
        columns: [
            {name: '', sorter: 'false'},
            {name: 'Name', width: 90},
            {name: 'Type', width: 10},
        ]
    });

    /**
     * The groups View
     */
    var GroupsView = View.extend({
        _load: function() {
            this.loadCollections([this.App.Data.Users]);
        },
        _render: function() {
            this.App.setTitle('Groups');
            this.registerView(new GroupsNavbarView(this.App), true);
            this.registerView(new GroupsTableView(
                this.App, {collection: this.App.Data.Groups}
            ), true);

            this.App.hideLoader();
        }
    });

    return GroupsView;
});
