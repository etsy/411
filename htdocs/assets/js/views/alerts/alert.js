"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        ModelView = require('views/model'),
        NavbarView = require('views/navbar'),
        ListView = require('views/list'),
        CollectionView = require('views/collection'),
        ActionsView = require('views/alerts/actions'),
        Renderer = require('views/renderer'),
        Templates = require('templates'),
        Util = require('util'),

        Alert = require('models/alert'),
        AlertCollection = require('collections/alert'),
        AlertLogCollection = require('collections/alertlog');


    var AlertLogView = View.extend({
        template: Templates['alerts/changelogentry'],

        simple: false,

        initialize: function(options) {
            this.simple = options.simple;
            this.listenTo(this.model, 'destroy', $.proxy(this.destroy, this));
        },
        _render: function() {
            var model_data = this.model.toJSON();
            var desc_parts = [];

            var alert = this.App.Data.Alerts.get(this.model.get('alert_id'));

            var users = this.App.Data.Users;
            var getName = function(user_id) {
                if(user_id === 0) {
                    return 'System';
                }

                return Util.getAssigneeName(0, user_id, users);
            };

            var level = 'default';
            switch(this.model.get('action')) {
                case 1:
                    level = 'info';
                    desc_parts.push('Alert created');
                    break;
                case 4:
                    level = 'danger';
                    desc_parts.push(getName(model_data.user_id));
                    desc_parts.push(['de-escalated', 'escalated'][model_data.a]);
                    break;
                case 5:
                    level = 'default';
                    desc_parts.push(getName(model_data.user_id));
                    var assign = model_data.a !== 0 || model_data.b !== 0;
                    desc_parts.push(assign ? 'assigned':'unassigned');
                    if(assign) {
                        desc_parts.push('to');
                        desc_parts.push(Util.getAssigneeName(model_data.a, model_data.b, this.App.Data.Users, this.App.Data.Groups));
                    }
                    break;
                case 6:
                    level = 'success';
                    desc_parts.push(getName(model_data.user_id));
                    desc_parts.push('marked');
                    desc_parts.push(Alert.Data().States[model_data.a]);
                    break;
                case 7:
                    level = 'success';
                    desc_parts.push(getName(model_data.user_id));
                    desc_parts.push('added a note');
                    break;
            }
            var vars = {
                alert_id: model_data.alert_id,
                name: model_data.name,
                simple: this.simple,
                level: level,
                description: desc_parts.join(' '),
                note: model_data.note,
                resolutions: Alert.Data().Resolutions,
                create_date: model_data.create_date,
                states: Alert.Data().States,
            };
            this.$el.html(this.template(vars));
        },
        selectAction: function(action) {
            switch(action) {
                case 'open': this.open(); break;
            }
        },
        open: function() {
            this.$('a')[0].click();
        },
    });

    var AlertLogTableView = ListView.extend({
        template: _.constant('<h1>Changelog</h1><div class="well well-sm col-xs-12 list"></div>'),
        className: 'col-xs-12',
        hiddenForm: true,
        fold: false,
        folded: false,

        subView: AlertLogView,
        initializeSubView: function(model) {
            return ListView.prototype.initializeSubView.call(this, model, {simple: true});
        }
    });

    var AlertNavbarView = NavbarView.extend({
        title: 'Alert',
        events: {
            'click .search-button': 'gotoSearch',
            'click .download-button': 'download',
        },
        sidelinks: [
            {name: 'Go to Search', action: 'search', icon: 'link'},
            {name: 'Download', action: 'download', icon: 'download-alt'}
        ],
        _render: function() {
            NavbarView.prototype._render.call(this);

            this.App.registerSelectableKbdShortcut('d', 'delete', 'Delete the current item', false);
        },
        download: function() {
            this.App.Bus.trigger('download');
        },
        gotoSearch: function() {
            this.App.Router.navigate('/search/' + this.model.get('search_id'), {trigger: true});
        }
    });

    var AlertFieldView = View.extend({
        template: Templates['alerts/alertfield'],
        tagName: 'tr',
        key: null,
        value: null,
        temp: false,
        events: {
            'click .edit-button': 'toggleEdit',
            'click .apply-button': 'apply',
            'click .delete-button': 'delete',
            'focus .content': 'copy',
        },
        initialize: function(options) {
            this.key = options.key;
            this.value = options.value;
            this.temp = options.temp;
        },
        _render: function() {
            this.$el.html(this.template({
                key: this.key, value: this.value, temp: this.temp
            }));

            var elem = this.registerElement('.renderer-select');
            Util.initRendererSelect(elem, Renderer.renderers);

            var content_elem = this.registerElement('.content');
            Util.autosize(content_elem);
            content_elem.on('keydown', function(e) {
                if(e.keyCode == 27) {
                    content_elem.blur();
                }
            });

            // Unfocus the select so that hotkeys work.
            var apply_button = this.$('.apply-button');
            elem.on('select2-close', function() {
                setTimeout(function() {
                    apply_button.focus();
                }, 0);
            });
        },
        selectAction: function(action) {
            switch(action) {
                case 'open': this.toggleEdit(); break;
                case 'delete': this.delete(); break;
            }
        },
        update: function(renderer_list, val) {
            this.$('.view').html(val);
            this.$('.renderer-select').select2('val', renderer_list);
        },
        delete: function() {
            if(this.temp) {
                this.destroy();
            }
        },
        toggleEdit: function(focus_sel) {
            var val_elem = this.$('.view');
            var edit_elem = this.$('.edit');
            var content_elem = this.$('.content');

            var edit = edit_elem.hasClass('hidden');
            val_elem.toggleClass('hidden', edit);
            edit_elem.toggleClass('hidden', !edit);

            if(edit) {
                content_elem.val(this.value);
                if(focus_sel) {
                    this.$('.renderer-select').select2('focus');
                } else {
                    this.$('.content').focus();
                }
            }
        },
        apply: function() {
            this.toggleEdit();
            if(this.temp) {
                this.value = this.$('.content').val();
            }

            this.processRenderers();
        },
        getRenderers: function() {
            var val = this.$('.renderer-select').select2('val');
            return val !== '' ? [val]:[];
        },
        processRenderers: function() {
            var val = this.getRenderers();
            this.trigger('update', this, val);
        },
        copy: function() {
            if(!this.temp) {
                this.trigger('copy', this);
                this.toggleEdit(true);
            }
        }
    });

    /**
     * The alert View
     */
    var AlertView = ModelView.extend({
        modelName: 'Alert',
        modelClass: Alert,
        modelUrl: '/alert/',

        template: Templates['alerts/alert'],

        _load: function(id) {
            // Load dependencies and the single requested alert.
            this.collection = new AlertLogCollection([], {id: id});
            this.loadCollectionsAndModel(
                [this.App.Data.Users, this.App.Data.Groups, this.collection],
                this.App.Data.Alerts, id
            );
        },
        _render: function() {
            this.App.setTitle('Alert: ' + this.model.get('id'));
            this.registerView(new AlertNavbarView(this.App, {model: this.model}), true);

            this.App.registerSelectableGroup(this);

            var vars = this.model.toJSON();
            _.extend(vars, {
                search_id: this.model.get('search_id'),
                states: Alert.Data().States,
                resolutions: Alert.Data().Resolutions,
                name: this.model.get('name'),
                assignee_name: Util.getAssigneeName(
                    this.model.get('assignee_type'), this.model.get('assignee'),
                    this.App.Data.Users, this.App.Data.Groups
                )
            });
            this.$el.append(this.template(vars));

            var content = this.model.get('content');
            var tbody = this.$('tbody');
            for(var k in content) {
                var fieldview = this.registerView(
                    new AlertFieldView(this.App, {key: k, value: content[k]}),
                    true, tbody, 'collection[]'
                );
                this.listenTo(fieldview, 'update', this.renderField);
                this.listenTo(fieldview, 'copy', this.copyField);
            }

            this.registerView(new AlertLogTableView(this.App, {collection: this.collection}), true);

            var view = this.registerView(new ActionsView(this.App), true, undefined, 'actions');
            var user_id = this.App.Data.User.id;
            var assignee_type = this.model.get('assignee_type');
            var assignee = this.model.get('assignee');
            var assignee_bit = assignee_type === 0 && assignee === user_id ? 2:
                (assignee_type === 0 && assignee === 0 ? 0:1);

            view.setData({
                count: 1,
                esc: 1 << this.model.get('escalated'),
                assign: 1 << assignee_bit,
                state: 1 << this.model.get('state'),
                single: true
            });
            view.rerender();

            Util.autosize(this.registerElement('textarea'));

            var alert_renderers = this.model.get('renderer_data');
            var search_renderers = search ? search.get('renderer_data'):null;
            var renderers = !_.isEmpty(alert_renderers) ? alert_renderers:
                            !_.isEmpty(search_renderers) ? search_renderers:{};

            if(!_.isEmpty(renderers)) {
                var mapping = Renderer.deserialize(this.App, renderers, this.model.id, this.getView('collection[]'));
                Renderer.process(this.App, mapping);
            } else {
                var mapping = {};
                mapping[this.model.id] = this.getView('collection[]');
                Renderer.autoProcess(this.App, mapping);
            }

            this.listenTo(this.App.Bus, 'download', this.downloadAlert);

            this.registerActions();

            this.App.hideLoader();
        },
        update: function() {
            this.rerender();
        },
        renderField: function(view, renderers) {
            this.App.showLoader();

            var mapping = {};
            mapping[this.model.id] = {};
            mapping[this.model.id][view.key] = [view, renderers];

            Renderer.process(this.App, mapping, $.proxy(this.App.hideLoader, this.App));
        },
        copyField: function(view) {
            var num = (Math.random() * 1000) | 0;
            var fieldview = this.registerView(
                new AlertFieldView(this.App, {key: view.key + '_' + num, value: view.value, temp: true}),
                false, undefined, 'collection[]'
            );
            view.$el.after(fieldview.el);
            fieldview.load();
            fieldview.update(view.getRenderers(), '');
            fieldview.toggleEdit();
            this.listenTo(fieldview, 'update', this.renderField);
            this.listenTo(fieldview, 'copy', this.copyField);
        },
        processAction: function(action) {
            var data = [this.model.get('id')];

            var func = this[action];
            if(_.isFunction(func)) {
                func.call(this, data);
            }
        },
        updateAlerts: function(action, data) {
            this.App.showLoader();

            return AlertCollection.action(action, data, {
                success: this.cbRendered(function(resp) {
                    this.App.addMessage('Alert update successful', 2);
                    delete data.id;
                    this.model.set(data);
                    this.update();
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        },
        downloadAlert: function() {
            var data = JSON.stringify(this.model);
            Util.download(data, 'alert_' + this.model.id + '.json');
        },
        savealertrenderers: function() {
            this.App.showLoader();

            var out_mapping = Renderer.serialize(this.getView('collection[]'));
            this.saveModel({renderer_data: out_mapping}, this.cbRendered(this.update));
        },
        savesearchrenderers: function() {
            this.App.showLoader();
            var search = this.App.Data.Searches.get(this.model.get('search_id'));
            var out_mapping = Renderer.serialize(this.getView('collection[]'));
            var data = {
                renderer_data: out_mapping,
                change_description: 'Modify renderer mapping'
            };

            search.save(data, {
                success: this.cbRendered(function(r) {
                    this.App.addMessage('Search update successful', 2);
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        }
    }, {
        AlertLogView: AlertLogView
    });
    _.extend(AlertView.prototype, ActionsView.ActionsMixin, CollectionView.SelectionMixin, {
        setSelectableDisplay: function(sel, selected, down) {
            $(sel.el).toggleClass('active', selected);
        },
    });

    return AlertView;
});
