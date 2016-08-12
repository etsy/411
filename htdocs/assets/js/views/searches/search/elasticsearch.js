"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates'),
        Util = require('util');

    require('views/searches/syntax/elasticsearch');


    /**
     * Elasticsearch search View.
     */
    var ElasticsearchSearchView = SearchView.SearchView.extend({
        addnFieldsBTpl: Templates['searches/search/elasticsearch/b'],
        addnFieldsDTpl: Templates['searches/search/elasticsearch/d'],
        __render: function() {
            var query_data = this.model.get('query_data') || {};
            var result_type = query_data.result_type || 0;

            var toggleResultType = $.proxy(function(result_type) {
                this.$('.fields-group').toggleClass('hidden', result_type !== 0);
                this.$('.filter-group').toggleClass('hidden', result_type === 2);
            }, this);

            // Set up the result_type radio and trigger it.
            var result_type_radio = this.registerElement('input[name=result_type]');
            result_type_radio.click(function(e) {
                toggleResultType(parseInt(e.currentTarget.value, 10));
            });
            result_type_radio.filter('input[value=' + result_type + ']').click();

            Util.initCodeMirror(this.registerElement('[name=query]'), {'mode': 'elasticquery'});
        },
        readForm: function() {
            var data = SearchView.SearchView.prototype.readForm.call(this);

            if("result_type" in data) {
                data.query_data.result_type = parseInt(data.result_type, 10);
                delete data.result_type;
            }

            data.query_data.filter_range = [null, null];
            if(data.filter_lo) {
                data.query_data.filter_range[0] = data.filter_lo;
                delete data.filter_lo;
            }

            if(data.filter_hi) {
                data.query_data.filter_range[1] = data.filter_hi;
                delete data.filter_lo;
            }

            if("event_time_based" in data) {
                data.query_data.event_time_based = parseInt(data.event_time_based, 10);
                delete data.event_time_based;
            }

            if('fields' in data) {
                var fields = data.fields.split(',');
                if(fields.length === 1 && fields[0] === '') {
                    fields = [];
                }
                data.query_data.fields = fields;
                delete data.fields;
            }

            return data;
        }
    });

    return ElasticsearchSearchView;
});
