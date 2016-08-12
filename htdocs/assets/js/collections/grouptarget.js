"use strict";
define(function(require) {
    var Collection = require('collection'),
        GroupTarget = require('models/grouptarget'),
        Config = require('config');


    var GroupTargetCollection = Collection.extend({
        model: GroupTarget,
        id: null,

        initialize: function(models, data) {
            this.id = 'id' in data ? data.id:0;
        },
        url: function() {
            return Config.api_root + 'group/' + this.id + '/target';
        },
    });

    return GroupTargetCollection;
});
