"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        TableView = require('views/table'),
        Templates = require('templates'),
        Renderer = require('views/renderer'),
        Util = require('util'),

        Search = require('models/search'),
        Alert = require('models/alert');


    var generateQuery = function(query) {
        var parts = [];
        for(var k in query) {
            var val = query[k];
            if(_.isArray(val)) {
                val = '(' + val.join(' ') + ')';
            }
            parts.push(
                k + ':' + val
            );
        }

        return parts.join(' AND ');
    };

    var AlertFieldView = View.extend({
        template: Templates['alerts/alertentryfield'],
        tagName: 'td',
        key: null,
        value: null,
        renderedValue: null,
        rendering: false,
        hideCb: null,
        renderers: null,
        initialize: function(options) {
            this.key = options.key;
            this.value = options.value;
            this.renderers = options.renderers;
        },
        _render: function() {
            this.$el.html(this.template({
                key: this.key, value: this.value
            }));

            if(this.renderers.length > 0) {
                this.$el.popover({
                    container: this.el,
                    content: '<img src="/assets/imgs/partload.gif" />',
                    animation: false,
                    template: '<div class="renderer-data popover" role="tooltip"><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
                    html: true,
                    placement: 'bottom',
                    trigger: 'manual'
                })
                .on('shown.bs.popover', this.cbLoaded(function(e) {
                    var popover = this.$el.data('bs.popover');
                    popover.$tip.css('top', parseInt(popover.$tip.css('top'), 10) - 22 + 'px');
                }))
                .on("mouseenter", this.cbLoaded(this.show))
                .on("mouseleave", this.cbLoaded(this.hide));
            }
        },
        _unrender: function() {
            var popover = this.$el.data('bs.popover');
            if(popover) {
                popover.destroy();
            }
        },
        show: function() {
            if(this.hideCb) {
                clearTimeout(this.hideCb);
                this.hideCb = null;
                return;
            }

            this.$el.popover('show');

            // If we have rendered data, just set it. Else, pull from renderers.
            if(!_.isNull(this.renderedValue)) {
                this.update(this.renderers, this.renderedValue, false);
            } else if(!this.rendering) {
                this.rendering = true;

                var mapping = {};
                mapping[this.key] = this.renderers;
                Renderer.deserialize(this.App, mapping, 0, [this]);
            }
        },
        hide: function() {
            if(this.hideCb) {
                clearTimeout(this.hideCb);
            }
            this.hideCb = setTimeout(this.cbLoaded(function() {
                this.hideCb = null;
                this.$el.popover('hide');
            }), 200);

            return false;
        },
        update: function(renderer_list, val, preview) {
            // This view might've been destroyed by the time the callback returns. We currently don't
            // associate callbacks with views, so just explicitly check that we're still loaded here.
            if(!this.loaded()) {
                return;
            }
            if(!preview) {
                this.renderedValue = val;
                var tip = this.$el.data('bs.popover').$tip;
                tip.find('.popover-content').html(val);
            } else {
                this.$('.view').html(val);
            }
        }
    });

    var AlertEntryView = View.extend({
        tagName: 'tr',
        template: Templates['alerts/alertentry'],
        keys: null,
        full: false,
        initialize: function(options) {
            this.full = options.full;
            this.keys = options.keys;
            this.preview = options.preview;
        },
        _render: function() {
            var search = this.App.Data.Searches.get(this.model.get('search_id'));
            var vars = this.model.toJSON();
            _.extend(vars, {
                preview: this.preview,
                full: this.full,
                search_id: search ? search.get('id'):0,
                states: Alert.Data().States,
                name: search ? search.get('name'):'Unknown',
                assignee_name: Util.getAssigneeName(
                    this.model.get('assignee_type'), this.model.get('assignee'),
                    this.App.Data.Users, this.App.Data.Groups
                )
            });
            this.$el.html(this.template(vars));
            var content = Util.flatten(this.model.get('content'));

            var alert_renderers = this.model.get('renderer_data');
            var search_renderers = search ? search.get('renderer_data'):null;
            var renderers = !_.isEmpty(alert_renderers) ? alert_renderers:
                            !_.isEmpty(search_renderers) ? search_renderers:{};

            for(var i = 0; i < this.keys.length; ++i) {
                var key = this.keys[i];
                var fieldview = this.registerView(new AlertFieldView(this.App, {
                    renderers: key in renderers ? renderers[key]:[],
                    key: key,
                    value: content[key]
                }), true, this.$el, 'field[]');
            }

            var mapping = Renderer.deserialize(this.App, renderers, this.model.id, this.getView('field[]'), undefined, true);
        },
        selectAction: function(action) {
            switch(action) {
                case 'open': this.open(); break;
                case 'select': this.select(); break;
                case 'source': this.source(); break;
            }
        },
        open: function() {
            this.$('a')[0].click();
        },
        select: function() {
            this.$('input')[0].click();
        },
        source: function() {
            this.$('a')[1].click();
        },
    });

    /**
     * An alert group View
     * Dislays related alerts together in a table.
     */
    var AlertGroupView = TableView.extend({
        tagName: 'div',
        template: Templates['alerts/alertgroup'],
        subView: AlertEntryView,
        sortable: false,
        selectable: true,

        // Title of this group.
        group_name: '',
        // A list of keys to display.
        keys: null,
        // Bitmask of attributes common to the selected alerts.
        alertData: 0,
        // List of selected alerts.
        alertList: null,
        // Indicates a list from the server.
        serverList: false,
        // Whether to display ALL fields.
        full: false,
        // Index of the last entry that was clicked.
        lastEntry: 0,

        events: {
            'click .fold-button': 'toggleFold',
            'click .alerts-load-button': 'loadMoreAlerts',
            'click .alert-checkbox': 'selectRange',
            'click .alerts-select-visible': 'selectVisibleAlerts',
            'click .alerts-status': 'selectAllAlerts',
            'click .source-link': 'gotoLink',
            'click .sort-button': 'toggleSort',
            'click .download-button': 'downloadAlerts',
            'click .render-button': 'toggleRender',
        },

        initialize: function(options) {
            TableView.prototype.initialize.call(this, options);
            this.full = options.full;
            this.group_name = options.group_name;
            this.preview = options.preview;
        },
        _render: function() {
            // These attributes are initialized here because we occasionally
            // need to rerender the View.
            this.columns = [];
            this.keys = [];
            var keys = {};
            // Generate a list of all keys that these alerts contain.
            for(var j = 0; j < this.collection.length; ++j) {
                for(var k in Util.flatten(this.collection.models[j].get('content'))) {
                    keys[k] = null;
                }
            }

            for(var k in keys) {
                this.columns.push({name: k});
                this.keys.push(k);
            }

            // Determine the priority of this group.
            var search = this.App.Data.Searches.get(this.collection.query.search_id);
            var priority = search ? search.get('priority'):0;
            var levels = ['info', 'warning', 'danger'];
            this.vars = {
                name: this.group_name,
                full: this.full,
                total: this.collection.total || this.collection.length,
                state: this.collection.query.state,
                priority: priority,
                level: levels[priority],
                search_id: this.collection.query.search_id,
                escalated: this.collection.query.escalated,
                states: Alert.Data().States,
                priorities: Search.Data().Priorities,
                more: this.collection.length < this.collection.total,
                preview: this.preview,
                sortable: this.sortable,
            };

            TableView.prototype._render.call(this);
        },
        initializeCollectionData: function(params) {
            return TableView.prototype.initializeCollectionData.call(this, params);
        },
        initializeSubView: function(model) {
            var view = this.registerView(new this.subView(this.App, {
                model: model, keys: this.keys, preview: this.preview, full: this.full
            }), false, undefined, 'collection[]');
            view.load();
            return view;
        },
        update: function() {
            // Overridden update method to just render.
            // Whenever a new set of alerts come in, we rerender the entire view.
            this.initializeCollection();
            this.updateSelection();
        },
        toggleFold: function(show) {
            var elem = this.$('.table-wrapper');
            var height = elem.height();
            if(!_.isBoolean(show)) {
                show = !height;
            }

            elem.stop().animate(show ? {height: elem[0].scrollHeight}:{height: 0});

            this.$('.fold-button')
                .toggleClass('glyphicon-collapse-up', !show)
                .toggleClass('glyphicon-collapse-down', show);
        },
        toggleSort: function() {
            this.sortable = !this.sortable;
            this.rerender();
        },
        toggleRender: function() {
            this.render = !this.render;
            this.rerender();
        },
        selectRange: function(e) {
            if(e.shiftKey) {
                var checked = e.target.checked;
                var elements = this.$('.alerts-table .alert-checkbox');

                var state = 0;
                for(var i = 0; i < elements.length; ++i) {
                    if(e.target.value == elements[i].value || this.lastEntry == elements[i].value) {
                        ++state;
                    }
                    if(state === 0) {
                        continue;
                    }
                    if(state === 2) {
                        break;
                    }
                    elements[i].checked = checked;
                }
            }
            this.lastEntry = e.target.value;

            this.updateSelection();
        },
        selectVisibleAlerts: function(e) {
            var checked = e.target.checked;
            var elements = this.$('.alerts-table .alert-checkbox');

            elements.prop('checked', checked);
            this.updateSelection();
        },
        selectAllAlerts: function() {
            if(this.alertList.length == this.collection.total) {
                return;
            }

            var data = {};
            var query = _.clone(this.collection.query);
            if('from' in query) {
                data.from = query.from;
                delete query.from;
            }
            if('to' in query) {
                data.to = query.to;
                delete query.to;
            }
            data.query = '(' + this.collection.base + ') AND ' + generateQuery(query);

            this.collection.getIds(data, {
                success: this.cbRendered(function(resp) {
                    this.alertList = resp;
                    this.serverList = true;
                    this.updateSelection();
                })
            });
        },
        // Populate selection data.
        updateSelection: function() {
            var data = {esc: 0, assign: 0, state: 0};
            var elements = this.$('.alerts-table .alert-checkbox');
            if(elements.length === 0) {
                return;
            }

            // First, calculate the number of checks.
            var values = elements.map(function(i, x) { return x.checked; });
            var checks = 0;
            for(var i = 0; i < values.length; ++i) {
                if(values[i]) {
                    ++checks;
                }
            }
            // Update the select-all box
            this.$('.alerts-select-visible')
                .prop('indeterminate', checks > 0 && checks < values.length)
                .prop('checked', values.length == checks);

            var status_element = this.$('.alerts-status');
            var display = values.length == checks && elements.length < this.collection.total;

            if(display) {
                status_element
                    .addClass('text-info')
                    .html(
                        this.serverList ? ('All ' + this.alertList.length + ' alerts in this group are selected'):
                        (elements.length + ' alerts are selected. Click here to select all alerts in this group')
                    );
            }
            status_element.toggleClass('hidden', !display);

            // If everything is checked and the list is from the server, our range of data
            // is indeterminate.
            var user_id = this.App.Data.User.id;
            if(values.length == checks && this.serverList) {
                data.esc = 1 | 2;
                data.assign = 1 | 2 | 4;
                data.state = 1 | 2 | 4;
            } else {
                this.serverList = false;
                var form = this.$('.alerts-table > tbody');
                var form_data = Util.serializeForm(form, true, true);
                this.alertList = 'alerts' in form_data ? form_data.alerts:[];

                // Update our bitmask.
                for(var j = 0; j < this.alertList.length; ++j) {
                    var model = this.collection.get(this.alertList[j]);
                    data.esc |= 1 << model.get('escalated')|0;
                    data.state = 1 << parseInt(model.get('state'), 10);

                    var assignee_type = model.get('assignee_type');
                    var assignee = model.get('assignee');
                    var assignee_bit = assignee_type === 0 && assignee === user_id ? 2:
                        (assignee_type === 0 && assignee === 0 ? 0:1);

                    data.assign = 1 << assignee_bit;
                }
            }
            this.alertData = data;
            this.App.Bus.trigger('selection');
        },
        // Load more alerts into the table.
        loadMoreAlerts: function(e) {
            var elem = $(e.currentTarget).closest('.alerts-load').find('input').get(0);
            var n = parseInt(elem.value, 10);

            var data = {
                offset: this.collection.length,
                count: n,
            };
            var query = _.clone(this.collection.query);
            if('from' in query) {
                data.from = query.from;
                delete query.from;
            }
            if('to' in query) {
                data.to = query.to;
                delete query.to;
            }
            data.query = '(' + this.collection.base + ') AND ' + generateQuery(query);


            this.collection.updateByQuery(data, {
                success: this.cbRendered(function(resp) {
                    for(var k in resp) {
                        this.collection.add(resp[k]);
                    }

                    // Rerender the alertgroup.
                    this.rerender();
                })
            });
            return false;
        },
        // Return the id of all alerts that have been selected in this view.
        getSelectionData: function() {
            return [this.alertData, this.alertList];
        },
        setSelectable: function(i, selected, down) {
            if(selected) {
                this.toggleFold(true);
            }

            TableView.prototype.setSelectable.call(this, i, selected, down);
        },
        gotoLink: function(e) {
            var elem = $(e.currentTarget);
            var id = elem.data('id');
            var model = new Alert({id: id});
            model.getLink({
                async: false,
                success: this.cbRendered(function(data) {
                    if(data.link !== null) {
                        window.open(data.link, '_blank');
                    } else {
                        this.App.addMessage('No source link found');
                    }
                })
            });
        },
        downloadAlerts: function() {
            var data = JSON.stringify(this.collection.models);
            var query_str = [];
            for(var k in this.collection.query) {
                query_str.push(k + '-' + this.collection.query[k]);
            }
            Util.download(data, 'alerts_' + query_str.join('_') + '.json');
        }
    }, {
        generateQuery: generateQuery
    });

    return AlertGroupView;
});
