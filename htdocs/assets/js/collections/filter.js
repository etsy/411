"use strict";
define(function(require) {
    var Collection = require('collection'),
        Filter = require('models/filter'),
        Config = require('config');


    var FilterCollection = Collection.extend({
        model: Filter,
        id: null,
        comparator: 'position',

        initialize: function(model, data) {
            this.id = 'id' in data ? data.id:0;
        },
        url: function() {
            return Config.api_root + 'search/' + this.id + '/filter';
        },
    });

    return FilterCollection;
});
