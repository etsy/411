"use strict";
define(function(require) {
    var Collection = require('collection'),
        List = require('models/list'),
        Config = require('config');


    var ListCollection = Collection.extend({
        model: List,
        url: Config.api_root + 'list',
    });

    return ListCollection;
});
