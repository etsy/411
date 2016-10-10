"use strict";
define(function(require) {
    var _ = require('underscore'),
        CollectionView = require('views/collection'),
        Templates = require('templates');


    /**
     * List View
     * Renders a collection of models via the given subView class.
     */
    var ListView = CollectionView.extend({
        template: Templates['list'],

        title: '',
        fold: false,
        folded: false,
        events: {},
        initialize: function() {
            CollectionView.prototype.initialize.call(this);
            this.events['click .fold-button'] = 'toggleFold';
        },

        _render: function() {
            CollectionView.prototype._render.call(this, {
                fold: this.fold,
                folded: this.folded,
                title: this.title,
                help: this.help,
            });
        },
        initializeCollection: function(params) {
            var arr = this.initializeCollectionData(params);
            var models = arr[0],
                frag = arr[1];

            this.$('.list')
                .text('')
                .append(frag);

            this.undim();
        },
        initializeSubView: function(model, options) {
            var view = CollectionView.prototype.initializeSubView.call(this, model, options);
            this.listenTo(view, 'delete', this.deleteModel);
            return view;
        },
        addModel: function(model) {
            var view = this.initializeSubView(model);
            this.collection.add(model);
            this.$('.list').append(view.el);
            this.trigger('change', this);
            this.toggleFold(true);
        },
        deleteModel: function(view) {
            view.destroy(true);
            view.model.destroy({defer: true});
            this.trigger('change', this);
            this.toggleFold(true);
        },
        toggleFold: function(show) {
            var elem = this.$('.panel-body');
            var visible = !!elem.height();
            if(!_.isBoolean(show)) {
                show = !visible;
            }
            if(visible && show) {
                elem.css({'height': 'auto'});
            } else {
                elem.stop().animate(show ? {height: elem[0].scrollHeight}:{height: 0});
            }

            this.$('.fold-button')
                .toggleClass('glyphicon-collapse-up', !show)
                .toggleClass('glyphicon-collapse-down', show);
        }
    });

    return ListView;
});
