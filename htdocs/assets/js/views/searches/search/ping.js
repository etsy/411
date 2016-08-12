"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates');


    /**
     * Ping search View.
     */
    var PingSearchView = SearchView.SearchView.extend({
        no_query: true,
        no_range: true,
        addnFieldsATpl: Templates['searches/search/ping/a'],
        readForm: function() {
            var data = SearchView.SearchView.prototype.readForm.call(this);

            if("host" in data) {
                data.query_data.host = data.host;
                delete data.host;
            }

            return data;
        }
    });

    return PingSearchView;
});
