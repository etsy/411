"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
        Model = require('model'),
        Data = require('data');


    /**
     * Target model
     */
    var Target = Model.extend({
        defaults: function() {
            return $.extend(true, {}, Data.Target.Defaults);
        },
        initialize: function(data) {
            _.defaults(this.attributes.data, _.mapObject(Target.Data().Data[this.get('type')], function(x) { return x[2]; }));
        },
        validate: function(data, options) {
            data = _.extend(this.toJSON(), data);

            options = options || {};
            options.url = this.collection.url() + '/validate';
            options.method = 'post';
            options.data = JSON.stringify(data);
            options.contentType = 'application/json; charset=utf-8';
            return Backbone.ajax(options);
        },
    }, {
        Data: function() { return Data.Target; }
    });

    return Target;
});
