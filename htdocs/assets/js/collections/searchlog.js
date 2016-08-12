"use strict";
define(function(require) {
    var Collection = require('collection'),
        SearchLog = require('models/searchlog'),
        Config = require('config');


    var SearchLogCollection = Collection.extend({
        model: SearchLog,
        id: null,
        comparator: 'create_date',

        initialize: function(models, data) {
            this.id = 'id' in data ? data.id:0;
        },
        url: function() {
            return Config.api_root + 'search/' + this.id + '/log';
        },
    });

    return SearchLogCollection;
});
