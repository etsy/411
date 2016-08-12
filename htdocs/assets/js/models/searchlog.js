"use strict";
define(function(require) {
    var $ = require('jquery'),
        Model = require('model'),
        Data = require('data');


    /**
     * Searchlog model
     */
    var SearchLog = Model.extend({
        defaults: function() {
            return $.extend(true, {}, Data.SearchLog.Defaults);
        },
    });

    return SearchLog;
});
