"use strict";
define(function(require) {
    var Collection = require('collection'),
        ReportTarget = require('models/reporttarget'),
        Config = require('config');


    var ReportTargetCollection = Collection.extend({
        model: ReportTarget,
        id: null,
        comparator: 'position',

        initialize: function(models, data) {
            this.id = 'id' in data ? data.id:0;
        },
        url: function() {
            return Config.api_root + 'report/' + this.id + '/target';
        },
    });

    return ReportTargetCollection;
});
