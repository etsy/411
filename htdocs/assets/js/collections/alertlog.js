"use strict";
define(function(require) {
    var Collection = require('collection'),
        AlertLog = require('models/alertlog'),
        Config = require('config');


    var AlertLogCollection = Collection.extend({
        model: AlertLog,
        id: null,
        comparator: 'create_date',

        initialize: function(models, data) {
            if(data) {
                this.id = data.id;
            }
        },
        url: function() {
            return Config.api_root + (this.id ? 'alert/' + this.id + '/log':'alertlog');
        },
    });

    return AlertLogCollection;
});
