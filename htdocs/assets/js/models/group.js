"use strict";
define(function(require) {
    var $ = require('jquery'),
        Model = require('model'),
        Data = require('data'),
        Config = require('config');


    /**
     * Group model
     */
    var Group = Model.extend({
        urlRoot: Config.api_root + 'group',
        defaults: function() {
            return $.extend(true, {}, Data.Group.Defaults);
        },
    }, {
        Data: function() { return Data.Group; }
    });

    return Group;
});
