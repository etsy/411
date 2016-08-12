"use strict";
define(function(require) {
    var Collection = require('collection'),
        Target = require('models/target'),
        Config = require('config');


    var TargetCollection = Collection.extend({
        model: Target,
        id: null,

        initialize: function(models, data) {
            this.id = 'id' in data ? data.id:0;
        },
        url: function() {
            return Config.api_root + 'search/' + this.id + '/target';
        },
    });

    return TargetCollection;
});
