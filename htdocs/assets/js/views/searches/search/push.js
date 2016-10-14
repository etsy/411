"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates'),
        Util = require('util');


    /**
     * Push search View.
     */
    var PushSearchView = SearchView.SearchView.extend({
        addnFieldsBTpl: Templates['searches/search/push/b'],
        no_query: true,
        no_freq: true,
        no_range: true,
        __render: function() {
            Util.initSelectAll(this.registerElement('input[name=push_url]'));
        }
    });

    return PushSearchView;
});
