"use strict";
define(function(require) {
    var $ = require('jquery'),
        Model = require('model'),
        Data = require('data');


    /**
     * Grouptarget model
     */
    var GroupTarget = Model.extend({
        defaults: function() {
            return $.extend(true, {}, Data.GroupTarget.Defaults);
        },
    });

    return GroupTarget;
});
