"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates');


    /**
     * HTTP search View.
     */
    var HTTPSearchView = SearchView.SearchView.extend({
        no_query: true,
        no_range: true,
        addnFieldsATpl: Templates['searches/search/http/a'],
        readForm: function() {
            var data = SearchView.SearchView.prototype.readForm.call(this);

            if("url" in data) {
                data.query_data.url = data.url;
                delete data.url;
            }

            if("code" in data) {
                data.query_data.code = parseInt(data.code, 10);
                delete data.code;
            }

            return data;
        }
    });

    return HTTPSearchView;
});
