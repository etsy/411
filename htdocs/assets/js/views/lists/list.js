"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        NavbarView = require('views/navbar'),
        ModelView = require('views/model'),
        Templates = require('templates'),
        Util = require('util'),

        List = require('models/list');


    var ListNavbarView = NavbarView.extend({
        title: 'List',
    });

    var ListInfoView = View.extend({
        template: Templates['lists/listinfo'],
        data: null,
        _load: function() {
            this.model.getInfo({
                success: this.cbLoaded(function(resp) {
                    this.data = resp;
                    this.render();
                }),
            });
        },
        _render: function() {
            this.$el.html(this.template(this.data));
        }
    });

    /**
     * The list View
     */
    var ListView = ModelView.extend({
        modelName: 'List',
        modelClass: List,
        modelUrl: '/list/',

        events: {
            'click #create-button': 'processSave',
            'click #update-button': 'processSave',
            'click #delete-button': 'showDelete',
        },
        template: Templates['lists/list'],
        _load: function(id) {
            this.loadCollectionsAndModel(
                [], this.App.Data.Lists, id
            );
        },
        _render: function() {
            this.App.setTitle('List: ' + (this.model.isNew() ? 'New':this.model.get('id')));
            this.registerView(new ListNavbarView(this.App), true);

            var vars = this.model.toJSON();
            _.extend(vars, {
                new_list: this.model.isNew(),
                types: List.Data().Types
            });
            this.$el.append(this.template(vars));

            if(!this.model.isNew()) {
                this.registerView(
                    new ListInfoView(this.App, {model: this.model}),
                    true, this.$('.panel-body'), 'info'
                );
            }

            this.detectChanges();

            this.App.hideLoader();
        },

        readForm: function() {
            var form = this.$('#list-form');
            return Util.serializeForm(form);
        },
        update: function() {
            var view = this.getView('info');
            if(view) {
                view.reload();
            }
        },
        saveModel: function(data) {
            ModelView.prototype.saveModel.call(this, data).success(this.cbRendered(this.update));
        },
        /**
         * Show the delete modal.
         */
        showDelete: function() {
            var view = this.App.setModal(new ModelView.DeleteModalView(this.App, this.modelName));
            this.listenTo(view, 'button:delete', this.destroyModel);
        },
        /**
         * Delete this model and redirect to the lists page.
         */
        destroyModel: function() {
            ModelView.prototype.destroyModel.call(this, '/lists');
        }
    });

    return ListView;
});
