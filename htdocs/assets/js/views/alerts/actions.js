"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        ModalView = require('views/modal'),
        TargetView = require('views/target'),
        Util = require('util'),
        Templates = require('templates'),

        Alert = require('models/alert'),
        Filter = require('models/filter'),
        Target = require('models/target'),
        AlertCollection = require('collections/alert');


    var SendModalView = ModalView.extend({
        title: 'Send',
        subTemplate: Templates['alerts/sendmodal'],
        alerts: null,
        initialize: function(options) {
            ModalView.prototype.initialize.call(this);
            this.alerts = options.alerts;
        },
        buttons: [
            {name: 'Send', icon: 'send', action: 'send', type: 'info'},
        ],
        events: {
            'click .send-button': 'send',
        },
        _render: function() {
            ModalView.prototype._render.call(this);

            var elem = this.registerElement('input[name=target]');
            Util.initTargetSelect(elem, Target.Data().Types);
            elem.change($.proxy(this.selectTarget, this));
        },
        selectTarget: function(e) {
            var elem = $(e.target);
            var type = e.target.value;
            if(!type.length) {
                return;
            }
            elem.select2('val', '');

            var target_type = this.$('.form-group');
            var target_form = this.$('.target-form');
            var target = new Target({type: type});
            var viewClass = TargetView.getSubclass(target.get('type'));
            var view = new viewClass(this.App, {model: target});
            view.temp = true;
            this.registerView(view, true, target_form, 'form');

            // If the form is deleted, reset everything.
            this.listenTo(view, 'delete', this.reset);
            target_type.hide();
        },
        reset: function() {
            this.getView('form').destroy();
            this.$('.form-group').show();
        },
        send: function() {
            var view = this.getView('form');
            if(!view) {
                this.hide();
                return false;
            }
            view.writeModel();

            AlertCollection.send({
                target: view.model.toJSON(),
                id: this.alerts
            }, {
                success: this.cbRendered(function(resp) {
                    this.App.addMessage('Sent ' + resp.count + ' alerts', 2);
                    this.hide();
                }),
            });
            return false;
        },
    });

    var WhitelistModalView = ModalView.extend({
        title: 'Whitelist',
        subTemplate: Templates['alerts/whitelistmodal'],
        alerts: null,
        initialize: function(options) {
            ModalView.prototype.initialize.call(this);
            this.alerts = options.alerts;
        },
        buttons: [
            {name: 'Whitelist', icon: 'asterisk', action: 'whitelist', type: 'info'},
        ],
        events: {
            'click .whitelist-button': 'whitelist',
        },
        _render: function() {
            ModalView.prototype._render.call(this);

            Util.initTimeSelect(this.registerElement('input[name=lifetime]'), {
                allow_zero: true,
                format: function(num) { return parseInt(num, 10) === 0 ? 'Forever':Util.formatTime(num); }
            });

            Util.autosize(this.registerElement('textarea'));
        },
        whitelist: function() {
            var data = Util.serializeForm(this.$('.modal-body'));
            data.id = this.alerts;

            AlertCollection.whitelist(data, {
                success: this.cbRendered(function(resp) {
                    this.App.addMessage('Whitelisted ' + resp.count + ' alerts', 2);
                    this.hide();
                }),
            });
            return false;
        },
    });

    var ActionModalView = ModalView.extend({
        title: 'Action',
        subTemplate: Templates['alerts/actionmodal'],
        buttons: [
            {name: 'Apply', icon: 'ok', action: 'action', type: 'success'},
        ],
        initialize: function(data) {
            var action = ActionsView.prototype.actions[data.action];
            this.title = action.name || this.title;
            if(action) {
                var button = _.clone(action);
                button.action = 'action';
                this.buttons = [button];
            }
        },
        events: {
            'click .action-button': 'action',
        },
        action: function() {
            var data = Util.serializeForm(this.$('.modal-body'), true);
            this.hideAndTrigger('action', _.noop, data);
            return false;
        }
    });

    var AssignModalView = ActionModalView.extend({
        subTemplate: Templates['alerts/assignmodal'],
        initialize: function() {
            ActionModalView.prototype.initialize.call(this, {action: 'assign'});
        },
        _render: function() {
            ModalView.prototype._render.call(this);

            Util.initAssigneeSelect(this.registerElement('input[name=assignee]'),
                this.App.Data.Users, this.App.Data.Groups, false
            );
        },
        action: function() {
            var data = Util.serializeForm(this.$('.modal-body'), true);
            // Fire the event if an assignee was picked.
            if('assignee' in data) {
                var parts = Util.parseAssignee(data.assignee);
                this.hideAndTrigger('action', _.noop, {
                    assignee_type: parts[0],
                    assignee: parts[1],
                    note: data.note
                });
            } else {
                this.hide();
            }
            return false;
        }
    });

    var ResolveModalView = ActionModalView.extend({
        subTemplate: Templates['alerts/resolvemodal'],
        initialize: function() {
            ActionModalView.prototype.initialize.call(this, {action: 'resolve'});
            this.vars = {resolutions: Alert.Data().Resolutions};
        },
    });

    /**
     * Alert Actions Mixin.
     * Provides useful functionality for pages with the action bar.
     */
    var ActionsMixin = {
        registerActions: function() {
            var actions = [
                'source',
                'compare',
                'send',
                'whitelist',
                'escalate',
                'deescalate',
                'assign',
                'assigntome',
                'unassign',
                'resolve',
                'acknowledge',
                'unresolve',
                'addnote',
                'savealertrenderers',
                'savesearchrenderers'
            ];
            for(var i = 0; i < actions.length; ++i) {
                this.listenTo(this.App.Bus, actions[i], _.partial(this.processAction, actions[i]));
            }
        },
        // Callbacks for the action buttons.
        source: function(alerts) {
            this.gotoLink(alerts[0]);
        },
        compare: function(alerts) {
            this.App.Router.navigate('/alerts/' + alerts.join(','));
            this.App.Bus.trigger('route');
        },
        send: function(alerts) {
            this.App.setModal(new SendModalView(this.App, {alerts: alerts}));
        },
        whitelist: function(alerts) {
            this.App.setModal(new WhitelistModalView(this.App, {alerts: alerts}));
        },
        escalate: function(alerts) {
            var view = new ActionModalView(this.App, {action: 'escalate'});
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.setAlertEscalation(alerts, data.note, true);
            });
        },
        deescalate: function(alerts) {
            var view = new ActionModalView(this.App, {action: 'deescalate'});
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.setAlertEscalation(alerts, data.note, false);
            });
        },
        assign: function(alerts) {
            var view = new AssignModalView(this.App);
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.setAlertAssignee(alerts, data.note, data.assignee_type, data.assignee);
            });
        },
        assigntome: function(alerts) {
            var view = new ActionModalView(this.App, {action: 'assigntome'});
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.setAlertAssignee(alerts, data.note, 0, this.App.Data.User.id);
            });
        },
        unassign: function(alerts) {
            var view = new ActionModalView(this.App, {action: 'unassign'});
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.setAlertAssignee(alerts, data.note, 0, 0);
            });
        },
        resolve: function(alerts) {
            var view = new ResolveModalView(this.App);
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.setAlertState(alerts, data.note, 2, data.resolution);
            });
        },
        acknowledge: function(alerts) {
            var view = new ActionModalView(this.App, {action: 'acknowledge'});
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.setAlertState(alerts, data.note, 1);
            });
        },
        unresolve: function(alerts) {
            var view = new ActionModalView(this.App, {action: 'unresolve'});
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.setAlertState(alerts, data.note, 0);
            });
        },
        addnote: function(alerts) {
            var view = new ActionModalView(this.App, {action: 'addnote'});
            this.App.setModal(view);
            this.listenTo(view, 'action', function(data) {
                this.addAlertNote(alerts, data.note);
            });
        },

        setAlertEscalation: function(alerts, note, escalated) {
            var data = {
                id: alerts,
                note: note,
                escalated: escalated
            };
            return this.updateAlerts('escalate', data);
        },
        setAlertState: function(alerts, note, state, resolution) {
            var data = {
                id: alerts,
                note: note,
                state: state
            };
            if(!_.isUndefined(resolution)) {
                data.resolution = resolution;
            }
            return this.updateAlerts('switch', data);
        },
        setAlertAssignee: function(alerts, note, assignee_type, assignee) {
            var data = {
                id: alerts,
                note: note,
                assignee_type: assignee_type,
                assignee: assignee
            };
            return this.updateAlerts('assign', data);
        },
        addAlertNote: function(alerts, note) {
            var data = {
                id: alerts,
                note: note
            };
            return this.updateAlerts('note', data);
        },
        gotoLink: function(id) {
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
        }
    };

    /**
     * Alert Actions bar.
     * Displays different buttons depending on what kind of alerts are currently selected.
     */
    var ActionsView = View.extend({
        tagName: 'div',
        settings: null,
        groups: null,

        template: Templates['alerts/actions'],
        actions: {
            source: {action: 'source', type: 'info', icon: 'link', name: 'Source', hotkey: 'u', help: 'Open up the source for this Alert'},
            compare: {action: 'compare', type: 'info', icon: 'th-list', name: 'Compare', hotkey: 'c', help: 'Compare the selected Alert(s)'},
            send: {action: 'send', type: 'info', icon: 'send', name: 'Send', hotkey: 'S', help: 'Send the selected Alert(s) to a Target'},
            whitelist: {action: 'whitelist', type: 'info', icon: 'asterisk', name: 'Whitelist', hotkey: 'W', help: 'Whitelist the selected Alert(s)'},
            escalate: {action: 'escalate', type: 'danger', icon: 'arrow-up', name: 'Escalate', hotkey: 'E', help: 'Escalate the selected Alert(s)'},
            deescalate: {action: 'deescalate', type: 'danger', icon: 'arrow-down', name: 'De-escalate', hotkey: 'D', help: 'De-escalate the selected Alert(s)'},
            assign: {action: 'assign', type: 'primary', icon: 'user', name: 'Assign', hotkey: 'A', help: 'Assign the selected Alert(s) to a target'},
            assigntome: {action: 'assigntome', type: 'primary', icon: 'inbox', name: 'Assign to Me', hotkey: 'M', help: 'Assign the selected Alert(s) to you'},
            unassign: {action: 'unassign', type: 'primary', icon: 'warning-sign', name: 'Unassign', hotkey: 'U', help: 'Unassign the selected Alert(s) as resolved'},
            resolve: {action: 'resolve', type: 'success', icon: 'ok', name: 'Resolve', hotkey: 'R', help: 'Mark the selected Alert(s) as resolved'},
            acknowledge: {action: 'acknowledge', type: 'success', icon: 'flash', name: 'Acknowledge', hotkey: 'K', help: 'Mark the selected Alert(s) as in progress'},
            unresolve: {action: 'unresolve', type: 'success', icon: 'remove', name: 'Unresolve', hotkey: 'N', help: 'Mark the selected Alert(s) as new'},
            addnote: {action: 'addnote', type: 'success', icon: 'floppy-disk', name: 'Add Note', hotkey: 'T', help: 'Add a note to the Alert'},
            savealertrenderers: {action: 'savealertrenderers', type: 'success', icon: 'th', name: 'Save Alert Renderers', hotkey: 'L', help: 'Save the current set of renderers to the Alert'},
            savesearchrenderers: {action: 'savesearchrenderers', type: 'success', icon: 'th-list', name: 'Save Search Renderers', hotkey: 'H', help: 'Save the current set of renderers to the Search'}
        },
        events: {
            'click .alert-action': 'action',
        },
        action: function(e) {
            var action = $(e.currentTarget).data('action');
            this.App.Bus.trigger(action);
        },
        _load: function(data) {
            this.settings = {};
            this.setData(data);
            this.render();
        },
        /**
         * Renders the list of actions that are possible.
         * A sample this.settings is as follows:
         * {
         *  count: 1,
         *  esc: 0,
         *  assign: 1,
         *  state: 1
         *  single: true
         * }
         */
        _render: function() {
            this.groups = [];

            if(this.settings.count > 0) {
                // Always allow these.
                this.groups.push([this.actions.send]);
                this.groups.push([this.actions.whitelist]);

                if(this.settings.count > 1) {
                    this.groups.push([this.actions.compare]);
                }
                if(this.settings.single) {
                    this.groups.push([this.actions.source]);
                }

                // 1=normal, 2=escalated
                var esc_group = [];
                if(this.settings.esc & 1) {
                    esc_group.push(this.actions.escalate);
                }
                if(this.settings.esc & 2) {
                    esc_group.push(this.actions.deescalate);
                }
                /** F_DIS
                if(esc_group.length) {
                    this.groups.push(esc_group);
                }
                */

                // 1=unassigned, 2=assigned, 4=assignedtome
                var assign_group = [];
                if(this.settings.assign & (1 | 2)) {
                    assign_group.push(this.actions.assigntome);
                }
                assign_group.push(this.actions.assign);
                if(this.settings.assign & (2 | 4)) {
                    assign_group.push(this.actions.unassign);
                }
                if(assign_group.length) {
                    this.groups.push(assign_group);
                }

                // 1=new, 2=acknowledged, 4=resolved
                var state_group = [];
                if(this.settings.state & (1 | 2)) {
                    state_group.push(this.actions.resolve);
                }
                if(this.settings.state & (1 | 4)) {
                    state_group.push(this.actions.acknowledge);
                }
                if(this.settings.state & (2 | 4)) {
                    state_group.push(this.actions.unresolve);
                }
                if(state_group.length === 0) {
                    state_group = [this.actions.resolve, this.actions.acknowledge, this.actions.unresolve];
                }
                this.groups.push(state_group);

                // Determine whether to show the save button. Used on the single Alert page.
                var misc_group = [this.actions.addnote];
                if(this.settings.single) {
                    misc_group.push(this.actions.savealertrenderers);
                    misc_group.push(this.actions.savesearchrenderers);
                }
                this.groups.push(misc_group);
            }

            for(var i = 0; i < this.groups.length; ++i) {
                for(var j = 0; j < this.groups[i].length; ++j) {
                    var entry = this.groups[i][j];
                    this.App.registerKbdShortcut(
                        entry.hotkey,
                        $.proxy(this.App.Bus.trigger, this.App.Bus, entry.action),
                        entry.help,
                        false
                    );
                }
            }

            var vars = {
                groups: this.groups,
                count: this.settings.count
            };
            this.$el.html(this.template(vars));
        },
        _unrender: function() {
            for(var i = 0; i < this.groups.length; ++i) {
                for(var j = 0; j < this.groups[i].length; ++j) {
                    var entry = this.groups[i][j];
                    this.App.destroyKbdShortcut(entry.hotkey);
                }
            }
        },
        setData: function(data) {
            _.extend(this.settings, data);
        }
    }, {
        ActionsMixin: ActionsMixin,
        ActionModalView: ActionModalView
    });

    return ActionsView;
});
