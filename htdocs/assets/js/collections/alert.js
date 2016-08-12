"use strict";
define(function(require) {
    var _ = require('underscore'),
        Backbone = require('backbone'),
        Collection = require('collection'),
        Alert = require('models/alert'),
        Config = require('config');


    var AlertCollection = Collection.extend({
        model: Alert,
        url: Config.api_root + 'alert',
        total: 0,
        base: {},
        query: {},
        initialize: function(raw, base, query, total) {
            this.base = base;
            this.query = query;
            this.total = total;
        },
        /**
         * Query ES for additional Alerts and add them to the collection.
         */
        updateByQuery: function(data, options) {
            return Backbone.ajax(_.extend({
                url: this.getUrl() + '/query',
                data: data,
            }, options));
        },
        /**
         * Get the ids for any Alerts that match the current query.
         */
        getIds: function(data, options) {
            return Backbone.ajax(_.extend({
                url: this.getUrl() + '/ids',
                data: data,
            }, options));
        },
    }, {
        /**
         * Apply 'action' to the given Alerts.
         */
        action: function(action, data, options) {
            return Backbone.ajax(_.extend({
                url: this.prototype.getUrl() + '/' + action,
                method: 'put',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify(data),
            }, options));
        },
        /**
         * Grab Alerts matching the current query.
         */
        bootstrap: function(data, options) {
            return Backbone.ajax(_.extend({
                url: this.prototype.getUrl() + '/bootstrap',
                data: data
            }, options));
        },
        /**
         * Whitelist the given Alerts by content_hash.
         */
        whitelist: function(data, options) {
            return Backbone.ajax(_.extend({
                url: this.prototype.getUrl() + '/whitelist',
                method: 'post',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify(data)
            }, options));
        },
        /**
         * Send Alerts to the provided target.
         */
        send: function(data, options) {
            return Backbone.ajax(_.extend({
                url: this.prototype.getUrl() + '/send',
                method: 'post',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify(data)
            }, options));
        },
    });

    return AlertCollection;
});
