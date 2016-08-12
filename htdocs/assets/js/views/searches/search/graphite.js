"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates');


    /**
     * Graphite search View.
     */
    var GraphiteSearchView = SearchView.SearchView.extend({
        no_range: true,
        addnFieldsBTpl: Templates['searches/search/graphite/b'],
        __render: function() {
            var query_data = this.model.get('query_data') || {};
            var filter_type = query_data.filter_type || 0;

            // Set up the result_type radio and trigger it.
            var filter_type_radio = this.registerElement('input[name=filter_type]');
            filter_type_radio.filter('input[value=' + filter_type + ']').click();
        },
        readForm: function() {
            var data = SearchView.SearchView.prototype.readForm.call(this);

            if("filter_hwa" in data) {
                data.query_data.filter_hwa = parseInt(data.filter_hwa, 10);
                delete data.filter_hwa;
            }

            if("filter_type" in data) {
                data.query_data.filter_type = parseInt(data.filter_type, 10);
                delete data.filter_type;
            }

            if("filter_threshold" in data) {
                data.query_data.filter_threshold = parseInt(data.filter_threshold, 10);
                delete data.filter_threshold;
            }

            return data;
        }
    });

    return GraphiteSearchView;
});
