"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        Model = require('model'),
        Data = require('data'),
        Config = require('config'),
        Util = require('util');


    /**
     * Report model
     */
    var Report = Model.extend({
        urlRoot: Config.api_root + 'report',
        defaults: function() {
            return $.extend(true, {}, Data.Report.Defaults);
        },
        generate: function(type, data) {
            data = _.extend(this.toJSON(), data);
            Util.request({
                method: 'post',
                url: this.urlRoot + '/' + this.id + '/generate/' + (type === 1 ? 'csv':'pdf'),
                data: data
            });
        }
    }, {
        Data: function() { return Data.Report; }
    });

    return Report;
});
