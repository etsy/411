"use strict";
define(function(require) {
    var $ = require('jquery'),
        Model = require('model'),
        Data = require('data');


    /**
     * Alertlog model
     */
    var AlertLog = Model.extend({
        defaults: function() {
            return $.extend(true, {}, Data.AlertLog.Defaults);
        }
    });

    return AlertLog;
});
