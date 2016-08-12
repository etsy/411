"use strict";
define(function(require) {
    var View = require('view'),
        NavbarView = require('views/navbar'),
        TableView = require('views/table'),
        Templates = require('templates'),

        List = require('models/list');


    var ListsNavbarView = NavbarView.extend({
        title: 'Lists',
        sidelinks: [
            {name: 'Create', icon: 'file', link: '/lists/new', action: 'create'}
        ]
    });

    var ListEntryView = View.extend({
        tagName: 'tr',
        template: Templates['lists/listentry'],
        _render: function() {
            var vars = this.model.toJSON();
            vars.types = List.Data().Types;
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

    var ListsTableView = TableView.extend({
        subView: ListEntryView,
        selectable: true,
        columns: [
            {name: '', sorter: 'false'},
            {name: 'Name', width: 40},
            {name: 'Type', width: 10},
            {name: 'URL', width: 50},
        ]
    });

    /**
     * The lists View
     */
    var ListsView = View.extend({
        _load: function() {
            this.loadCollections([this.App.Data.Users]);
        },
        _render: function() {
            this.App.setTitle('Lists');
            this.registerView(new ListsNavbarView(this.App), true);
            this.registerView(new ListsTableView(
                this.App, {collection: this.App.Data.Lists}
            ), true);

            this.App.hideLoader();
        }
    });

    return ListsView;
});
