"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        View = require('view'),
        ModalView = require('views/modal'),
        NavbarView = require('views/navbar'),
        CollectionView = require('views/collection'),
        AlertGroupView = require('views/alerts/alertgroup'),
        SearchesView = require('views/searches/searches'),
        ActionsView = require('views/alerts/actions'),
        QueryParser = require('queryparser'),
        Handlebars = require('handlebars'),
        Templates = require('templates'),
        Moment = require('moment'),
        Util = require('util'),
        Data = require('data'),
        URI = require('uri'),

        Alert = require('models/alert'),
        Search = require('models/search'),
        AlertCollection = require('collections/alert');


    var TIME_FMT = 'YYYY/MM/DD HH:mm:ss';

    var AlertsSearchModal = ModalView.extend({
        title: 'Alert',
        subTemplate: Templates['alerts/searchmodal'],
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
                categories: Search.Data().Categories,
                priorities: Search.Data().Priorities,
                types: Search.Data().Types,
                states: Alert.Data().States,
            };
        },
        _render: function() {
            ModalView.prototype._render.call(this);

            // Initialize all the various selects.
            Util.initTags(this.registerElement('.tags'));
            Util.initAssigneeSelect(
                this.registerElement('input[name=assignee]'),
                this.App.Data.Users, this.App.Data.Groups, true
            );
            Util.initSearchSelect(
                this.registerElement('input[name=search_id]'),
                this.App.Data.Searches, true
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
            this.$('input[name=search_id]').select2('data', null);
            this.$('input[name=owner]').select2('data', null);
        },
        search: function() {
            var form = this.$('form');
            var query = Util.serializeForm(form, true);

            if('assignee' in query) {
                var assignee = Util.parseAssignee(query.assignee);
                query.assignee_type = assignee[0];
                query.assignee = assignee[1];
            }

            if('tags' in query) {
                query.tags = query.tags.split(',');
            }

            this.trigger('search', AlertGroupView.generateQuery(query));
            return false;
        }
    });

    var AlertsNavbarView = NavbarView.extend({
        title: 'Alerts',
        search: true,
        entryTpl: Templates['alerts/queryentry'],
        links: [
            {name: 'Toggle All', action: 'toggleall'},
        ],
        sidelinks: [
            {name: 'Feed', icon: 'globe', link: '/alerts/feed'},
            {name: 'Search', icon: 'search', action: 'search'},
        ],
        searchlinks: [
            {divider: true},
            {name: 'Set as default', action: 'default', icon: 'heart-empty'},
            {name: 'Save', action: 'save', icon: 'floppy-disk'},
        ],
        events: {
            'click .toggleall-button': 'toggleAll',
            'click .search-button': 'showSearch',
            'click .default-button': 'setDefaultQuery',
            'click .save-button': 'saveQuery',
            'click .navbar-search-button': 'executeQuery',
            'click .query-button': 'loadQuery',
            'click .delete-button': 'deleteQuery',
            'submit .navbar-search form': 'executeQuery',
        },
        _render: function() {
            NavbarView.prototype._render.call(this);

            var default_search = this.App.Data.User.get('settings')['default'];
            var query = Util.parseQuery(window.location.href);
            var query_string = query.query || default_search || 'state:(0 OR 1)';

            var queries = this.App.Data.User.get('settings')['queries'] || [];
            for(var i = 0; i < queries.length; ++i) {
                this.$('.navbar-searchlinks').prepend(this.entryTpl({name: queries[i], i: i}));
            }

            var query_bar = this.$('.navbar-search-input');

            // Query bar shortcuts
            this.App.registerKbdShortcut('/', function() {
                query_bar.focus();
                return false;
            }, 'Focus search bar');
            query_bar.on('keydown', function(e) {
                if(e.keyCode == 27) {
                    query_bar.blur();
                }
            });

            this.updateQuery(query_string, query.from, query.to, true);

            this.App.registerSelectableKbdShortcut('x', 'select', 'Toggle selection of the current Alert', false);
            this.App.registerSelectableKbdShortcut('u', 'source', 'Open source of the current Alert', false);

            // Initialize the datetime pickers.
            var dtp_config = {
                useStrict: true,
                useSeconds: true,
                format: TIME_FMT
            };
            this.registerElement('.time-a, .time-b').datetimepicker(dtp_config);
        },
        toggleAll: function() {
            this.App.Bus.trigger('toggleall');
        },

        // Query methods.

        /**
         * Update the default query for the current user.
         */
        setDefaultQuery: function() {
            var query = this.$('.navbar-search-input').val();
            var user = this.App.Data.User;
            var settings = user.get('settings');
            settings['default'] = query;

            user.save();
        },
        /**
         * Save the current query to user settings.
         */
        saveQuery: function() {
            var query = this.$('.navbar-search-input').val();
            var user = this.App.Data.User;
            var settings = user.get('settings');
            settings['queries'] = settings['queries'] || [];
            settings['queries'].push(query);

            user.save();
            this.rerender();
        },
        /**
         * Load the query from user settings and call executeQuery.
         */
        loadQuery: function(e) {
            var i = $(e.target.parentNode).data('index');
            var user = this.App.Data.User;
            var settings = user.get('settings');

            this.$('.navbar-search-input').val(settings['queries'][i]);
            this.executeQuery();
        },
        /**
         * Delete the query from user settings.
         */
        deleteQuery: function(e) {
            var i = $(e.target.parentNode).data('index');
            var user = this.App.Data.User;
            var settings = user.get('settings');
            settings['queries'].splice(i, 1);

            user.save();
            this.rerender();
        },

        /**
         * Write a query object to the navbar.
         */
        updateQuery: function(query, from, to, replace) {
            this.$('.navbar-search-input').val(query);
            var params = {
                query: query
            };

            if(!_.isUndefined(from)) {
                this.$('.time-a input[name=from]').val(from);
                params.from = from;
            }
            if(!_.isUndefined(to)) {
                this.$('.time-b input[name=to]').val(to);
                params.to = to;
            }
            this.App.Router.navigate('/alerts?' + decodeURIComponent($.param(params)), {trigger: false, replace: replace});
        },
        /**
         * Retrieve the query from the navbar and submit it for processing.
         */
        executeQuery: function() {
            var query = Util.serializeForm(this.$('.search-form'), true);

            if(query.from) {
                var from_moment = Moment.utc(query.from, TIME_FMT);
                if(from_moment.isValid()) {
                    query.from = from_moment.unix();
                }
            } else {
                delete query.from;
            }
            if(query.to) {
                var to_moment = Moment.utc(query.to, TIME_FMT);
                if(to_moment.isValid()) {
                    query.to = to_moment.unix();
                }
            } else {
                delete query.to;
            }

            this.App.Bus.trigger('route', query);
            return false;
        },
        showSearch: function() {
            var modal = this.App.setModal(new AlertsSearchModal(this.App));
            this.listenTo(modal, 'search', $.proxy(function(data) {
                this.updateQuery(data);
                this.executeQuery();
            }, this));
        },
    });

    /**
     * Very simple view which wraps multiple alert groups.
     */
    var AlertGroupsView = View.extend({
        tagName: 'div',
        template: Templates['alerts/alertgroups'],
        title: '',
        query: null,
        groups: null,
        initialize: function(options) {
            this.title = options.title;
            this.query = options.query;
            this.groups = options.groups;
        },
        _render: function() {
            this.$el.html(this.template({title: this.title, query: $.param(this.query)}));
            var sel = this.$('.groups');
            for(var i = 0; i < this.groups.length; ++i) {
              sel.append(this.groups[i].el);
            }
        }
    });

    var AlertsQueryView = CollectionView.extend({
        subView: AlertGroupView,
        // Unlike the standard CollectionView, we have many collections.
        collections: null,
        query: null,
        initialize: function() {
            this.collections = [];
            CollectionView.prototype.initialize.call(this);
        },
        update: function() {
            var query = Util.parseQuery(window.location.href);

            var processResults = this.cbRendered(function(resp) {
                this.collections = [];

                for(var i = 0; i < resp.length; ++i) {
                    var c = resp[i].count;
                    var collection_query = {
                        assignee: parseInt(c.assignee, 10),
                        assignee_type: parseInt(c.assignee_type, 10),
                        escalated: c.escalated || 0,
                        search_id: parseInt(c.search_id, 10),
                        state: parseInt(c.state, 10),
                    };
                    if('from' in query) {
                        collection_query.from = query.from;
                    }
                    if('to' in query) {
                        collection_query.to = query.to;
                    }
                    this.collections.push(
                        new AlertCollection(
                            resp[i].data,
                            query.query,
                            collection_query,
                            parseInt(c.count, 10)
                        )
                    );
                }

                this.initializeCollection();
                this.App.Bus.trigger('selection');
            });

            this.App.showLoader();

            AlertCollection.bootstrap(query, {
                success: processResults,
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        },
        initializeCollectionData: function(params) {
            var frag = document.createDocumentFragment();
            this.destroyViews();

            var data = {};

            var setIfUndef = function(k, x, v) {
                if(!(k in x)) {
                    x[k] = v;
                }
                return x[k];
            };

            for(var i = 0; i < this.collections.length; ++i) {
                var query = this.collections[i].query;
                var node = null;
                var search = this.App.Data.Searches.get(query.search_id);
                var priority = search ? search.get('priority'):0;

                // Walk into the object until we arrive at the destination bucket we want to fill.
                node = setIfUndef(query.escalated, data, {});
                node = setIfUndef(query.assignee_type, node, {});
                node = setIfUndef(query.assignee, node, {});
                node = setIfUndef(priority, node, {});
                node = setIfUndef(query.search_id, node, {});
                if(!(query.state in node)) {
                    node[query.state] = this.collections[i];
                } else {
                    throw 'Duplicate Alert Group!';
                }
            }

            // Iterate over the collections in a specific order and use the data
            // to build up the final DOM.

            // Escalated
            var esc_types = _.keys(data);
            esc_types.reverse();
            for(var i in esc_types) {
                // Assignee type
                var assignee_types = data[esc_types[i]];
                for(var j in assignee_types) {
                    // Assignee
                    var assignees = assignee_types[j];
                    for(var k in assignees) {
                        var groups = [];
                        // Priority
                        var priorities = assignees[k];
                        var prio_types = _.keys(priorities);
                        prio_types.reverse();
                        for(var l in prio_types) {
                            // Search id
                            var searches = priorities[prio_types[l]];
                            for(var m in searches) {
                                // Active
                                var states = searches[m];
                                for(var n in states) {
                                    var collection = states[n];
                                    var search = this.App.Data.Searches.get(m);
                                    var group_name = search ? search.get('name'):'Unknown';

                                    // AlertGroupViews are children of AlertGroupsViews. However, we
                                    // want to have direct access to these children so we register them
                                    // here instead of in the AlertGroupsViews.
                                    var view = this.registerView(new AlertGroupView(
                                        this.App, {
                                        group_name: group_name,
                                        collection: collection,
                                    }), false, undefined, 'collection[]');
                                    view.load();
                                    groups.push(view);
                                }
                            }
                        }

                        // Only render something if we have views.
                        if(groups.length) {
                            var assignee_type = parseInt(j, 10);
                            var assignee = parseInt(k, 10);
                            var title = Util.getAssigneeName(
                                assignee_type, assignee, this.App.Data.Users, this.App.Data.Groups, undefined, true
                            );
                            var view = new AlertGroupsView(this.App, {
                                title: title, query: {assignee_type: assignee_type, assignee: assignee}, groups: groups
                            });
                            view.load();
                            frag.appendChild(view.el);
                        }
                    }
                }
            }

            return [null, frag];
        },
    });

    /**
     * The alerts View
     * This View is used for displaying a specific list of ids or displaying the results
     * of a query.
     */
    var AlertsView = View.extend({
        _load: function() {
            this.loadCollections([this.App.Data.Users, this.App.Data.Groups, this.App.Data.Searches]);
        },
        _render: function() {
            this.App.setTitle('Alerts');

            // The navbar will update the url, firing off a route event when it does so.
            // Update the View when this happens.
            this.listenTo(this.App.Bus, 'route', this.update);
            this.listenTo(this.App.Bus, 'selection', this.updateActions);
            this.listenTo(this.App.Bus, 'toggleall', this.toggleAll);

            var navbar = this.registerView(new AlertsNavbarView(this.App), true, undefined, 'nav');
            this.registerView(new AlertsQueryView(this.App), true, undefined, 'list');
            this.registerView(new ActionsView(this.App), true, undefined, 'actions');

            this.registerActions();

            this.App.hideLoader();
        },

        processAction: function(action) {
            var groups = this.getView('list').getView('collection[]');
            var data = [];
            for(var i = 0; i < groups.length; ++i) {
                var g_data = groups[i].getSelectionData();
                data = data.concat(g_data[1]);
            }

            if(data.length === 0) {
                return;
            }

            var func = this[action];
            if(_.isFunction(func)) {
                func.call(this, data);
            }
        },

        /**
         * Toggle the fold state of all groups
         */
        toggleAll: function() {
            var groups = this.getView('list').getView('collection[]');
            for(var i = 0; i < groups.length; ++i) {
                groups[i].toggleFold();
            }
        },
        /**
         * Update the view.
         */
        update: function(query) {
            var view = this.getView('list');
            var navbar = this.getView('nav');

            // Ignore if the query param is empty.
            if(_.isUndefined(query)) {
                return;
            }
            this.App.Router.navigate('/alerts?' + decodeURIComponent($.param(query)));

            // Execute our changes.
            view.update();
        },
        updateActions: function() {
            var view = this.getView('list');
            var data = {esc: 0, assign: 0, state: 0, count: 0};
            if(view) {
                var groups = view.getView('collection[]');
                for(var i = 0; i < groups.length; ++i) {
                    var g_data = groups[i].getSelectionData();
                    for(var k in g_data[0]) {
                        if(k in data) {
                            data[k] |= g_data[0][k];
                        }
                    }
                    data.count += g_data[1].length;
                }
            }

            var actions = this.getView('actions');
            actions.setData(data);
            actions.rerender();
        },
        updateAlerts: function(action, data) {
            this.App.showLoader();

            return AlertCollection.action(action, data, {
                success: this.cbRendered(function(resp) {
                    this.App.addMessage('Updated ' + resp.count + ' alerts', 2);
                    var query = Util.parseQuery(window.location.href);

                    // Wait 1 second before updating the UI. Why?
                    // Elasticsearch updates its indices every second.
                    // The _search endpoint uses these indices, so
                    // we wait for them to become (more) consistent.
                    _.delay(this.cbRendered(this.update), 1000, query);
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        }
    });
    _.extend(AlertsView.prototype, ActionsView.ActionsMixin);

    return AlertsView;
});
