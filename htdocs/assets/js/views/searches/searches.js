"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        View = require('view'),
        ModalView = require('views/modal'),
        NavbarView = require('views/navbar'),
        CollectionView = require('views/collection'),
        TableView = require('views/table'),
        Templates = require('templates'),
        Util = require('util'),

        Search = require('models/search');


    var SearchesCreateModal = ModalView.extend({
        title: 'Create',
        subTemplate: Templates['searches/createmodal'],
        buttons: [
            {name: 'Create', type: 'success', action: 'create'},
        ],
        events: {
            'click .create-button': 'create',
        },
        initialize: function() {
            ModalView.prototype.initialize.call(this);
            this.vars = {types: Search.Data().Types};
        },
        create: function() {
            var form = this.$('form');
            var query = Util.serializeForm(form, true);

            this.App.Router.navigate('/searches/new?' + decodeURIComponent($.param(query)), {trigger: true});
            return false;
        }
    });

    var SearchesSearchModal = ModalView.extend({
        title: 'Search',
        subTemplate: Templates['searches/searchmodal'],
        buttons: [
            {name: 'Clear', type: 'default', action: 'clear', persist: true, clear: true},
            {name: 'Search', type: 'primary', icon: 'search', action: 'search'},
        ],
        events: {
            'click .search-button': 'search',
            'click .clear-button': 'clear',
        },
        initialize: function() {
            ModalView.prototype.initialize.call(this);
            this.vars = {
                types: Search.Data().Types,
                categories: Search.Data().Categories,
                priorities: Search.Data().Priorities,
            };
        },
        _render: function() {
            ModalView.prototype._render.call(this);

            Util.initTags(this.registerElement('.tags'));
            Util.initAssigneeSelect(
                this.registerElement('input[name=assignee]'),
                this.App.Data.Users, this.App.Data.Groups, true
            );
            Util.initUserSelect(
                this.registerElement('input[name=owner]'),
                this.App.Data.Users, true
            );
        },
        clear: function() {
            // A reset button will clear all the normal fields.
            // We need to manually clear any js form elements here.
            this.$('.tags').select2('data', null);
            this.$('input[name=assignee]').select2('data', null);
            this.$('input[name=owner]').select2('data', null);
        },
        search: function() {
            var form = this.$('form');
            var query = Util.serializeForm(form, true);
            if('tags' in query) {
                query.tags = query.tags.split(',');
            }
            if('assignee' in query) {
                var assignee = Util.parseAssignee(query.assignee);
                query.assignee_type = assignee[0];
                query.assignee = assignee[1];
            }

            this.App.Router.navigate('/searches?' + decodeURIComponent($.param(query)));
            this.App.Bus.trigger('route');
            return false;
        }
    });

    var SearchesNavbarView = NavbarView.extend({
        title: 'Searches',
        links: [
            {name: 'Compact View', action: 'toggle'}
        ],
        sidelinks: [
            {name: 'Create', icon: 'file', action: 'create'},
            {name: 'Search', icon: 'search', action: 'search'},
        ],
        events: {
            'click .toggle-button': 'toggleText',
            'click .create-button': 'showCreate',
            'click .search-button': 'showSearch',
        },
        toggle: false,
        _render: function() {
            NavbarView.prototype._render.call(this);

            this.App.registerSelectableKbdShortcut('l', 'clone', 'Clone the current item', false);
        },
        toggleText: function() {
            this.toggle = !this.toggle;
            this.$('.toggle-button').text(this.toggle ? 'Detailed View':'Compact View');
            this.App.Bus.trigger('toggle', this.toggle);
        },
        showCreate: function() {
            this.App.setModal(new SearchesCreateModal(this.App));
        },
        showSearch: function() {
            this.App.setModal(new SearchesSearchModal(this.App));
        }
    });

    // A function to filter the collection. Used by both collection views.
    var filterSearchesFunc = function(collection, query) {
        for(var k in query) {
            if(!_.isArray(query[k])) {
                query[k] = [query[k]];
            }
        }

        var map_bool = _.partial(_.map, _, function(x) { return !!parseInt(x, 10); });
        var map_int = _.partial(_.map, _, function(x) { return parseInt(x, 10); });
        // Much ado about types. Make sure we convert to the correct data types.
        if('enabled' in query) {
            // Cast from "0"|"1" string to bool.
            query.enabled = map_bool(query.enabled);
        }
        if('priority' in query) {
            query.priority = map_int(query.priority);
        }
        if('assignee' in query) {
            query.assignee_type = map_int(query.assignee_type);
            query.assignee = map_int(query.assignee);
        }
        if('owner' in query) {
            query.owner = map_int(query.owner);
        }

        // Filter on the query.
        var models = _.isEmpty(query) ?
            collection.models:
            collection.filter(function(model) {
                for(var k in query) {
                    var fval = query[k];
                    var mval = model.get(k);
                    mval = _.isArray(mval) ? mval:[mval];
                    var match = _.any(_.map(mval, _.partial(_.contains, fval)));
                    if(!match) {
                        return false;
                    }
                }
                return true;
            });
        return models;
    };

    var filterSearchesQueryFunc = function(collection) {
        return filterSearchesFunc(collection, Util.parseQuery(window.location.href));
    };

    var SearchListEntryView = View.extend({
        template: Templates['searches/searchlistentry'],
        events: {
            'change .status-button': 'toggleStatus',
        },
        _render: function() {
            var vars = this.model.toJSON();
            vars.categories = Search.Data().Categories;
            vars.priorities = Search.Data().Priorities;
            vars.types = Search.Data().Types;
            vars.owner_name = Util.getUserName(this.model.get('owner'), this.App.Data.Users);
            vars.assignee_name = Util.getAssigneeName(
                this.model.get('assignee_type'), this.model.get('assignee'),
                this.App.Data.Users, this.App.Data.Groups,
                undefined, true
            );
            var last_exec_date = this.model.get('last_execution_date');
            var last_fail_date = this.model.get('last_failure_date');
            vars.healthy = !(last_exec_date === last_fail_date && last_exec_date > 0);

            this.$el.html(this.template(vars));
        },
        selectAction: function(action) {
            switch(action) {
                case 'open': this.open(); break;
                case 'clone': this.clone(); break;
            }
        },
        open: function() {
            this.$('a')[0].click();
        },
        clone: function() {
            this.$('.clone-button').click();
        },
        /**
         * Toggle the enabled state of the Search.
         */
        toggleStatus: function(e) {
            var enabled = e.target.checked;
            var change_desc = (enabled ? 'Enabled':'Disabled') + ' search';

            this.model.save({enabled: enabled, change_description: change_desc}, {
                success: this.cbRendered(function(resp) {
                    this.App.addMessage('Updated search', 2);
                })
            });
        }
    });

    var SearchesListView = CollectionView.extend({
        subView: SearchListEntryView,
        selectable: true,
        initializeCollectionData: function(params) {
            var arr = CollectionView.prototype.initializeCollectionData.call(this);
            var children = arr[1].children;
            var count = children.length;

            // Insert a div after every 3 Views to line up the results.
            for(var i = count - 1; i >= 2; --i) {
                if((i + 1) % 3 === 0) {
                    $(children[i]).after($('<div class="clearfix">'));
                }
            }

            return arr;
        },
        filterCollection: filterSearchesQueryFunc,
        setSelectableDisplay: function(sel, selected, down) {
            $(sel.el).find('.panel')
                .toggleClass('panel-primary', selected)
                .toggleClass('panel-default', !selected);
        }
    });

    var SearchTableEntryView = View.extend({
        tagName: 'tr',
        template: Templates['searches/searchtableentry'],
        _render: function() {
            var vars = this.model.toJSON();
            vars.categories = Search.Data().Categories;
            vars.priorities = Search.Data().Priorities;
            vars.types = Search.Data().Types;

            var last_exec_date = this.model.get('last_execution_date');
            var last_fail_date = this.model.get('last_failure_date');
            vars.healthy = !(last_exec_date === last_fail_date && last_exec_date > 0);
            this.$el.html(this.template(vars));
        },
        selectAction: function(action) {
            switch(action) {
                case 'open': this.open(); break;
                case 'clone': this.clone(); break;
            }
        },
        open: function() {
            this.$('a')[0].click();
        },
        clone: function() {
            this.$('.clone-button').click();
        },
    });

    var SearchesTableView = TableView.extend({
        subView: SearchTableEntryView,
        events: {
            'click .update-button': 'updateSearches',
        },
        columns: [
            {name: '', sorter: 'false'},
            {name: '', sorter: 'false'},
            {name: 'Name', width: 50},
            {name: 'Category'},
            {name: 'Tags', width: 40},
            {name: 'Type'},
            {name: 'Priority'},
            {name: 'Health'},
            {name: 'Enabled', sorter: 'check'},
        ],
        sortColumn: 2,
        selectable: true,
        buttons: [
            {name: 'Update', type: 'primary', icon: 'arrow-up', action: 'update'}
        ],
        filterCollection: filterSearchesQueryFunc,
        /**
         * Update the status (enabled/disabled) of the Searches on the page.
         */
        updateSearches: function() {
            var raw = Util.serializeForm(this.$('table'));
            var data = [];
            for(var k in raw) {
                var enabled = !!raw[k];
                var change_desc = (enabled ? 'Enabled':'Disabled') + ' search';
                var search = this.App.Data.Searches.get(k);
                if(search.get('enabled') != enabled) {
                    data.push({id: k, enabled: enabled, change_description: change_desc});
                }
            }
            this.App.showLoader();

            this.App.Data.Searches.save(data, {
                success: this.cbRendered(function(resp) {
                    this.App.addMessage('Updated ' + resp.length + ' searches', 2);
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        }
    });

    /**
     * The searches View
     */
    var SearchesView = View.extend({
        _load: function() {
            this.loadCollections([this.App.Data.Users, this.App.Data.Groups]);
        },
        _render: function() {
            this.App.setTitle('Searches');
            this.registerView(new SearchesNavbarView(this.App), true);
            this.toggleView();

            // The navbar will update the url, firing off a route event when it does so.
            // Update the View when this happens.
            this.listenTo(this.App.Bus, 'route', this.update);
            this.listenTo(this.App.Bus, 'toggle', this.toggleView);

            this.App.hideLoader();
        },
        toggleView: function(toggle) {
            if(this.getView('collection')) {
                this.destroyView('collection');
            }
            var newView = toggle ? SearchesListView:SearchesTableView;

            this.registerView(new newView(
                this.App, {collection: this.App.Data.Searches}
            ), true, undefined, 'collection');
        },
        update: function() {
            var view = this.getView('collection');
            if(view) {
                view.update();
            }
        }
    }, {
        filterSearchesFunc: filterSearchesFunc
    });

    return SearchesView;
});
