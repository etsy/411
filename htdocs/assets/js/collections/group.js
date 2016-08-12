"use strict";
define(function(require) {
    var Collection = require('collection'),
        Group = require('models/group'),
        Config = require('config');


    var GroupCollection = Collection.extend({
        model: Group,
        url: Config.api_root + 'group',
    });

    return GroupCollection;
});
