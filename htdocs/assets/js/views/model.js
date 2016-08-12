"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        Handlebars = require('handlebars'),
        View = require('view'),
        ModalView = require('views/modal'),
        Util = require('util');


    var DeleteModalView = ModalView.extend({
        subTemplate: Handlebars.compile('Are you sure you want to delete this {{ type }}?'),
        title: 'Delete',
        buttons: [
            {name: 'Delete', type: 'danger', icon: 'trash', action: 'delete'}
        ],
        initialize: function(type) {
            ModalView.prototype.initialize.call(this);
            this.vars = {type: type};
        }
    });

    /**
     * Base model View
     */
    var ModelView = View.extend({
        // Name of the model.
        modelName: '',
        // Type of the model.
        modelClass: null,
        // Base url of the model.
        modelUrl: null,
        // Whether the model has any changes pending.
        pendingChanges: false,

        /**
         * A convenience method to set the pendingChanges bit.
         */
        setPendingChanges: function() {
            this.pendingChanges = true;
        },
        /**
         * A convenience method to clear the pendingChanges bit.
         */
        clearPendingChanges: function() {
            this.pendingChanges = false;
        },
        /**
         * A convenience method to call loadCollections and additionally load a model.
         * @param {Array} collections - An array of Backbone collections to update.
         * @param {Collection} collection - Target collection to load model into.
         * @param {int} id - ID of the model to load.
         * @param {Function} func - A callback to execute once all Deferred are resolved.
         * @param {Array} deferred - An array of additional Deferred to resolve.
         */
        loadCollectionsAndModel: function(collections, collection, id, func, deferred) {
            if(_.isUndefined(func)) func = this.render;
            if(_.isUndefined(deferred)) deferred = [];
            var wrapped_func = func;

            if(id) {
                deferred.push(collection.update({data: {id: id}}));
                wrapped_func = $.proxy(function() {
                    this.model = collection.get(id);
                    func.apply(this);
                }, this);
            } else {
                this.model = new this.modelClass();
            }
            View.prototype.loadCollections.call(this, collections, wrapped_func, deferred);
        },
        /**
         * A convenience method to detect changes to any input elements and set the
         * pendingChanges bit accordingly.
         */
        detectChanges: function() {
            this.registerElement('input, textarea, select').change($.proxy(function() {
                this.pendingChanges = true;
            }, this));
        },
        /**
         * Abort if there are unsaved changes.
         */
        onExit: function() {
            // Check sub Views and return if not true.
            var ret = View.prototype.onExit.call(this);
            if(!_.isBoolean(ret) || !ret) {
                return ret;
            }

            // Check if there are any changes to save.
            if(this.model && (this.model.isNew() || this.pendingChanges)) {
                return 'There are unsaved changes. Are you sure you wish to leave this page?';
            }
            return true;
        },
        // Retrieve all data from the form and return it.
        readForm: function() {
            return Util.serializeForm(this.$el);
        },
        // Write any modifications to the model.
        writeModel: function() {
            this.model.set(this.readForm());
        },
        // Load data from the form and submit it to the server.
        processSave: function() {
            var data = this.readForm();
            this.saveModel(data);
            return false;
        },
        /**
         * Save this model and execute any callbacks.
         * If the model is new, will redirect to the canonical url.
         * @param {Function} success - A callback to execute if the request is successful.
         */
        saveModel: function(data, success) {
            var new_model = this.model.isNew();
            this.App.showLoader();

            var options = {
                success: this.cbRendered(function(r) {
                    this.pendingChanges = false;
                    this.App.addMessage(this.modelName + (new_model ? ' creation successful':' update successful'), 2);
                    if(success) success(this.model);

                    if(new_model) {
                        this.App.Router.navigate(this.modelUrl + this.model.get('id'), {trigger: true});
                    }
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            };
            return this.model.save(data, options);
        },
        /**
         * Delete this model and redirect to another location.
         * @param {string} url - The url to navigate to on success.
         */
        destroyModel: function(url) {
            this.App.showLoader();

            return this.model.destroy({
                success: this.cbRendered(function() {
                    this.pendingChanges = false;
                    this.App.addMessage(this.modelName + ' deletion successful', 2);
                    this.App.Router.navigate(url, {trigger: true});
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        },

        /**
         * Extended to listen to change events and update pendingChanges.
         */
        registerView: function() {
            var view = View.prototype.registerView.apply(this, arguments);
            this.listenTo(view, 'change', this.setPendingChanges);
            return view;
        }
    }, {
        DeleteModalView: DeleteModalView,
    });

    return ModelView;
});
