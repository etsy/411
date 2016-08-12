"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        Util = require('util'),
        View = require('view');


    var SelectionMixin = {
        selectionIndex: -1,
        /**
         * Get the number of selectable items.
         */
        getSelectableCount: function() {
            return this.getSelectables().length;
        },
        /**
         * Get all selectables.
         */
        getSelectables: function() {
            return this.getView('collection[]');
        },
        /**
         * Set the selectable.
         * @param {int} i - The index of the selectable.
         * @param {boolean} selected - Whether the selectable was selected or deselected.
         * @param {boolean} down - Whether the motion was down or up.
         */
        setSelectable: function(i, selected, down) {
            this.selectionIndex = selected ? i:-1;

            var collection = this.getSelectables();
            var sel = collection[i];
            this.setSelectableDisplay(sel, selected, down);

            // Scroll page to show selectable.
            if(selected) {
                var rect = sel.el.getBoundingClientRect();

                var $window = $(window);
                var bounds = {top: 70, bottom: 70, left: 0, right: 0};
                if(!Util.visible(rect, bounds)) {
                    if(down) {
                        window.scrollTo($window.scrollLeft(), $window.scrollTop() + rect.top - bounds.top);
                    } else {
                        window.scrollTo($window.scrollLeft(), $window.scrollTop() - ($window.height() - rect.bottom - bounds.bottom));
                    }
                }
            }
        },
        /**
         * Set up any changes to the selectable when selected or deselected.
         * @param {int} i - The index of the selectable.
         * @param {boolean} selected - Whether the selectable was selected or deselected.
         * @param {boolean} down - Whether the motion was down or up.
         */
        setSelectableDisplay: function(sel, selected, down) {},
        /**
         * Notify the selectable that it has been selected..
         * @param {string} action - An action.
         * @param {int} i - The index of the selectable.
         */
        selectSelectable: function(action, i) {
            var collection = this.getSelectables();
            if(i >= collection.length) {
                return;
            }

            collection[i].selectAction(action);
        },
        render: function() {
            View.prototype.render.call(this);

            if(this.selectionIndex >= 0) {
                this.setSelectable(this.selectionIndex, true, true);
            }
        }
    };

    /**
     * Collection View
     * Base view for rendering multiple models.
     */
    var CollectionView = View.extend({
        tagName: 'div',
        className: 'col-xs-12',
        template: _.constant(''),

        // The view to render for each model in the collection.
        subView: null,
        // Variables to pass to the view.
        vars: null,
        // Whether the inputs in this container are hidden from serializeForm.
        hiddenForm: false,
        // Whether to automatically register this as a selectable group.
        selectable: false,

        initialize: function() {
            if(this.selectable) {
                this.App.registerSelectableGroup(this);
            }
            this.vars = _.clone(this.vars) || {};
            if(this.collection) {
                this.listenTo(this.collection, 'sync', this.clearPendingChanges);
            }
        },
        _render: function(vars) {
            this.$el.html(this.template(_.extend(vars, this.vars)));
            if(this.hiddenForm) {
                this.$el.addClass('hidden-form');
            }
            this.update();
        },
        update: function(params) {
            this.collection.update({
                success: $.proxy(this.initializeCollection, this, params),
                fail: $.proxy(this.App.hideLoader, this.App)
            });
        },
        clear: function(params) {
            // Clear the collection, but keep the lastTimestamp field.
            var ts = this.collection.lastTimestamp;
            this.collection.reset();
            this.collection.lastTimestamp = ts;
            this.destroyViews();
        },
        /**
         * Generate all the data to insert into the DOM.
         */
        initializeCollectionData: function(params) {
            var frag = document.createDocumentFragment();
            this.destroyViews();

            // Construct and add the Views to the fragment.
            var t = this;
            var models = this.filterCollection(this.collection);
            for(var i = 0; i < models.length; ++i) {
                var view = t.initializeSubView(models[i]);
                frag.appendChild(view.el);
            }
            return [models, frag];
        },
        /**
         * (Re)initialize the collection and add it to the DOM.
         */
        initializeCollection: function(params) {
            var arr = this.initializeCollectionData(params);
            var models = arr[0],
                frag = arr[1];

            this.$el
                .text('')
                .append(frag);
        },
        /**
         * Set up a sub View and return it.
         * @param {Model} model - The model to initialize the View with.
         * @return {View} The new View object.
         */
        initializeSubView: function(model, options) {
            options = options || {};
            options.model = model;
            var view = this.registerView(new this.subView(this.App, options), false, undefined, 'collection[]');
            view.load();
            return view;
        },
        /**
         * Determine what models will get displayed in the table.
         * @param {Collection} collection - The collection of models.
         * @return {Array} - An array of Models to use.
         */
        filterCollection: function(collection) {
            return collection.models;
        },
        /**
         * Clear the pendingChanges bit from any subviews.
         */
        clearPendingChanges: function() {
            var views = this.getView('collection[]');
            for(var i = 0; i < views.length; ++i) {
                if(!views[i].clearPendingChanges) {
                    continue;
                }
                views[i].clearPendingChanges();
            }
        },
        destroy: function() {
            // By default, we want to remove the root node.
            View.prototype.destroy.call(this, true);
        },
    }, {
        SelectionMixin: SelectionMixin
    });
    _.extend(CollectionView.prototype, SelectionMixin);

    return CollectionView;
});
