"use strict";
define(function(require) {
    var _ = require('underscore'),
        d = require('text!data_json');

    var data = {};

    var reload = function(d) {
        var D = JSON.parse(d);
        for(var k in data) {
            delete data[k];
        }
        _.extend(data, D);
        data.reload = reload;
    };
    reload(d);

    return data;
});
