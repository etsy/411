"use strict";
define(function(require) {
    var $ = require('jquery'),
        Backbone = require('backbone'),
        Model = require('model'),
        Data = require('data'),
        Config = require('config');


    /**
     * List model
     */
    var List = Model.extend({
        urlRoot: Config.api_root + 'list',
        defaults: function() {
            return $.extend(true, {}, Data.List.Defaults);
        },
        getInfo: function(options) {
            options = options || {};
            options.url = this.urlRoot + '/' + this.id + '/info';
            return Backbone.ajax(options);
        },
    }, {
        Data: function() { return Data.List; }
    });

    return List;
});
