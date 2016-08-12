"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        Model = require('model'),
        Backbone = require('backbone'),
        Data = require('data'),
        Config = require('config');


    /**
     * Alert model
     */
    var Alert = Model.extend({
        urlRoot: Config.api_root + 'alert',
        defaults: function() {
            return $.extend(true, {}, Data.Alert.Defaults);
        },
        /**
         * Fetch the source link for this Alert.
         * @param {Object} options - Parameters to pass to jquery's ajax function.
         */
        getLink: function(options) {
            options = options || {};
            options.url = this.urlRoot + '/' + this.id + '/link';
            return Backbone.ajax(options);
        },
    }, {
        Data: function() { return Data.Alert; }
    });

    return Alert;
});
