"use strict";
define(function(require) {
    var Collection = require('collection'),
        User = require('models/user'),
        Config = require('config');


    var UserCollection = Collection.extend({
        model: User,
        url: Config.api_root + 'user',
    });

    return UserCollection;
});
