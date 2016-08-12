"use strict";
define(function(require) {
    var _ = require('underscore'),
        NavbarView = require('views/navbar'),
        ModelView = require('views/model'),
        ListView = require('views/list'),
        Templates = require('templates'),
        Util = require('util'),

        Group = require('models/group'),
        GroupTarget = require('models/grouptarget'),
        GroupTargetCollection = require('collections/grouptarget');


    var GroupNavbarView = NavbarView.extend({
        title: 'Group',
    });

    var GroupTargetEntryView = ModelView.extend({
        tagName: 'li',
        template: Templates['groups/grouptarget'],
        events: {
            'click .close-button': 'delete',
        },
        _render: function() {
            this.$el.addClass('col-lg-3 col-md-4 col-sm-6 col-xs-12');
            var name = 'Unknown';
            switch(this.model.get('type')) {
                case 0:
                    name = 'User: ' + Util.getUserName(this.model.get('user_id'), this.App.Data.Users);
                    break;
                case 1:
                    name = 'Email: ' + this.model.get('data');
                    break;
            }
            var vars = {'name': name};

            this.$el.html(this.template(vars));
        },
        delete: function() {
            this.trigger('delete', this);
        }
    });

    var GroupTargetsListView = ListView.extend({
        title: 'Targets',
        subView: GroupTargetEntryView,
        help: 'Select a user from the list or type in an email to add an entry to the Group.',
        events: {
            'change .select': 'create',
        },
        _render: function() {
            ListView.prototype._render.call(this);

            // Initialize the target select.
            // Use a user select as the base and add the ability to input emails.
            var users = this.App.Data.Users;
            Util.initUserSelect(
                this.registerElement('.select'),
                this.App.Data.Users, true, {
                    placeholder: '',
                    initSelection: null,
                    createSearchChoice: function(term) {
                        return {id: '1' + term, text: term};
                    },
                    query: function(q) {
                        var term = q.term.toLowerCase();
                        var results = _.map(
                            users.filter(function(model) {
                                return model.get('real_name').toString().toLowerCase().indexOf(term) !== -1;
                            }),
                            function(model) {
                                return {id: "0" + model.id, text: model.get('real_name') + ''};
                            }
                        );

                        q.callback({results: results});
                    }
                }
            );
        },
        create: function(e) {
            var val = e.target.value;
            if(_.isUndefined(val)) {
                return;
            }

            var type = parseInt(val.charAt(0), 10);
            var target = val.substr(1);
            this.$('.select').select2('val', '');

            // Populate all the necessary information for this model.
            var data = {type: type, group_id: parseInt(this.collection.id, 10), user_id: 0, data: ''};
            if(type === 0) {
                data.user_id = parseInt(target, 10);
            } else {
                data.data = target;
            }

            this.addModel(new GroupTarget(data));
        }
    });

    /**
     * The group View
     */
    var GroupView = ModelView.extend({
        modelName: 'Group',
        modelClass: Group,
        modelUrl: '/group/',

        events: {
            'click #create-button': 'processSave',
            'click #update-button': 'processSave',
            'click #delete-button': 'showDelete',
        },
        template: Templates['groups/group'],
        _load: function(id) {
            this.collection = new GroupTargetCollection([], {id: id});

            // Only fetch the collection if we're looking at an existing group.
            var deferred = [];
            if(id) {
                deferred.push(this.collection.update());
            }

            this.loadCollectionsAndModel(
                [this.App.Data.Users],
                this.App.Data.Groups, id,
                undefined,
                deferred
            );
        },
        _render: function() {
            this.App.setTitle('Group: ' + (this.model.isNew() ? 'New':this.model.get('id')));
            this.registerView(new GroupNavbarView(this.App), true);

            var vars = this.model.toJSON();
            _.extend(vars, {
                new_group: this.model.isNew(),
                types: Group.Data().Types
            });
            this.$el.append(this.template(vars));
            // Only render the list if the model is saved.
            if(!this.model.isNew()) {
                this.registerView(
                    new GroupTargetsListView(this.App, {collection: this.collection}),
                    true, this.$('.target-list')
                );
            }

            this.detectChanges();

            this.App.hideLoader();
        },
        readForm: function() {
            var form = this.$('#group-form');
            return Util.serializeForm(form);
        },
        processSave: function() {
            var data = this.readForm();

            if(!this.model.isNew()) {
                this.collection.save();
            }
            this.saveModel(data);
            return false;
        },
        /**
         * Show the delete modal.
         */
        showDelete: function() {
            var view = this.App.setModal(new ModelView.DeleteModalView(this.App, this.modelName));
            this.listenTo(view, 'button:delete', this.destroyModel);
        },
        /**
         * Delete this model and redirect to the groups page.
         */
        destroyModel: function() {
            ModelView.prototype.destroyModel.call(this, '/groups');
        }
    });

    return GroupView;
});
