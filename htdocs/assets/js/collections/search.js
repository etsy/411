"use strict";
define(function(require) {
    var Collection = require('collection'),
        Search = require('models/search'),
        Config = require('config');


    var SearchCollection = Collection.extend({
        model: Search,
        url: Config.api_root + 'search',
    });

    return SearchCollection;
});
