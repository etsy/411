"use strict";
define(function(require) {
    var Collection = require('collection'),
        Report = require('models/report'),
        Config = require('config');


    var ReportCollection = Collection.extend({
        model: Report,
        url: Config.api_root + 'report',
    });

    return ReportCollection;
});
