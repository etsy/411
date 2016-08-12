"use strict";
define(function(require) {
    var $ = require('jquery'),
        Model = require('model'),
        Data = require('data');


    /**
     * Reporttarget model
     */
    var ReportTarget = Model.extend({
        defaults: function() {
            return $.extend(true, {}, Data.ReportTarget.Defaults);
        },
    });

    return ReportTarget;
});
