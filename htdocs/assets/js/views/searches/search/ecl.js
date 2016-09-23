"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates'),
        Util = require('util');

    require('views/searches/syntax/ecl');


    /**
     * Elasticsearch search View.
     */
    var ECLSearchView = SearchView.SearchView.extend({
        __render: function() {
            Util.initCodeMirror(this.registerElement('[name=query]'), {'mode': 'ecl'});
        }
    });

    return ECLSearchView;
});
