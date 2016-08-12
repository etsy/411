"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        Templates = require('templates');


    /**
     * Threatexchange search View.
     */
    var ThreatexchangeSearchView = SearchView.SearchView.extend({
        addnFieldsATpl: Templates['searches/search/threatexchange/a'],
        readForm: function() {
            var data = SearchView.SearchView.prototype.readForm.call(this);
            if('threatexchange_type' in data) {
                data.query_data.threatexchange_type = data.threatexchange_type;
                delete data.threatexchange_type;
            }

            return data;
        }
    });

    return ThreatexchangeSearchView;
});
