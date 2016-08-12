"use strict";
define(function(require) {
    var $ = require('jquery'),
        Model = require('model'),
        Data = require('data'),
        Config = require('config');


    /**
     * User model
     */
    var User = Model.extend({
        urlRoot: Config.api_root + 'user',
        defaults: function() {
            return $.extend(true, {}, Data.User.Defaults);
        },
    });

    return User;
});
