"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates');


    /**
     * Push search View.
     */
    var PushSearchView = SearchView.SearchView.extend({
        addnFieldsBTpl: Templates['searches/search/push/b'],
        no_query: true,
        no_freq: true,
        no_range: true,
        __render: function() {
            this.registerElement('input[name=push_url]').click(function() {
                this.setSelectionRange(0, this.value.length);
            });
        }
    });

    return PushSearchView;
});
