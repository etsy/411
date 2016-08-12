"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates');


    /**
     * Null search View.
     */
    var NullSearchView = SearchView.SearchView.extend({
        no_query: true,
        no_range: true,
    });

    return NullSearchView;
});
