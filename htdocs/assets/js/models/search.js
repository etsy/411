"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
        Model = require('model'),
        Data = require('data'),
        Config = require('config');


    var Search = Model.extend({
        urlRoot: Config.api_root + 'search',
        defaults: function() {
            return $.extend(true, {}, Data.Search.Defaults);
        },
        getStats: function(options) {
            options = options || {};
            options.url = this.urlRoot + '/' + this.id + '/stats';
            return Backbone.ajax(options);
        },
        getJobs: function(options) {
            options = options || {};
            options.url = this.urlRoot + '/' + this.id + '/job';
            return Backbone.ajax(options);
        },
        getPreviewNotif: function(data, options) {
            data = _.extend(this.toJSON(), data);

            options = options || {};
            options.url = this.urlRoot + '/preview';
            options.method = 'post';
            options.data = JSON.stringify(data);
            options.contentType = 'application/json; charset=utf-8';
            return Backbone.ajax(options);
        },
        test: function(data, options) {
            data = _.extend(this.toJSON(), data);

            options = options || {};
            options.url = this.urlRoot + '/test';
            options.method = 'post';
            options.data = JSON.stringify(data);
            options.contentType = 'application/json; charset=utf-8';
            return Backbone.ajax(options);
        },
        execute: function(data, options) {
            data = _.extend(this.toJSON(), data);

            options = options || {};
            options.url = this.urlRoot + '/' + this.id + '/execute';
            options.method = 'post';
            options.data = JSON.stringify(data);
            options.contentType = 'application/json; charset=utf-8';
            return Backbone.ajax(options);
        }
    }, {
        Data: function() { return Data.Search; }
    });

    return Search;
});
