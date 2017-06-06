"use strict";
define(function(require) {
    var CollectionView = require('views/collection'),
        Templates = require('templates');


    /**
     * Table View
     * Renders a collection of models via the given subView class.
     */
    var TableView = CollectionView.extend({
        template: Templates['table'],

        // Definition for the table columns.
        columns: null,
        // Whether to enable sorting.
        sortable: true,
        // Views in sorted order.
        orderedCollection: null,
        // Show a placeholder if there are no results.
        emptyPlaceholder: true,

        initialize: function() {
            CollectionView.prototype.initialize.call(this);
            this.orderedCollection = [];
        },
        _render: function() {
            CollectionView.prototype._render.call(this, {
                columns: this.columns,
                buttons: this.buttons,
            });
        },
        /**
         * (Re)build the table.
         */
        initializeCollection: function(params) {
            var arr = this.initializeCollectionData(params);
            var models = arr[0],
                frag = arr[1];

            var show_table = !!models.length || !this.emptyPlaceholder;

            // Clean up the old table, if it exists.
            this.$('.results-wrapper table')
                .trigger('destroy');

            this.$('.noresults-wrapper').toggleClass('hidden', show_table);
            this.$('.results-wrapper').toggleClass('hidden', !show_table);

            if(show_table) {
                this.$('.results-wrapper table tbody')
                    .text('')
                    .append(frag);
                this.$('.count').text(models.length);

                var sortColumn = [];
                if(this.sortColumn) {
                    sortColumn.push([this.sortColumn]);
                }

                // Set up the new table.
                if(this.sortable) {
                    this.$('.results-wrapper table')
                        .bind("sortEnd", $.proxy(this.syncCollectionOrder, this))
                        .tablesorter({
                            delayInit: true,
                            sortList: sortColumn,
                            theme: 'bootstrap',
                            widgets: ['uitheme'],
                            headerTemplate: '{content} {icon}',
                        });
                }

                this.syncCollectionOrder();
            } else {
                this.$('hidden').show();
            }

            this.undim();
        },
        setSelectableDisplay: function(sel, selected, down) {
            $(sel.el).toggleClass('active', selected);
        },
        /**
         * Synchronize the order of the collection[] views with
         * the order of the elements in the DOM. This is necessary
         * so that keyboard navigation works.
         */
        syncCollectionOrder: function() {
            var rows = this.$('.results-wrapper table tbody > tr');
            var views = this.getView('collection[]');
            var oldIndex = this.selectionIndex;

            if(oldIndex >= 0) {
                this.setSelectable(oldIndex, false, true);
            }

            this.orderedCollection = [];
            // Record the offset of each element.
            for(var i = 0; i < rows.length; ++i) {
                $(rows.get(i)).data('tmp_index', i);
            }
            for(var i = 0; i < views.length; ++i) {
                var x = views[i].$el.data('tmp_index');
                this.orderedCollection[x] = views[i];
            }

            if(oldIndex >= 0) {
                this.setSelectable(oldIndex, true, true);
            }
        },
        getSelectables: function() {
            return this.orderedCollection;
        }
    });

    return TableView;
});
