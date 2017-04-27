"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        ChartView = require('views/chart'),
        Templates = require('templates'),
        Config = require('config'),
        Data = require('data');


    /**
     * The index View
     */
    var IndexView = View.extend({
        template: Templates['index'],
        data: null,
        _load: function() {
            // If logged in, load up dashboard data.
            if(this.App.Data.User) {
                this.App.ajax({
                    url: Config.api_root + 'dashboard',
                    success: this.cbLoaded(function(resp) {
                        this.data = resp;
                        this.render();
                    }),
                    complete: $.proxy(this.App.hideLoader, this.App)
                });
            } else {
                this.render();
            }
        },
        _render: function() {
            this.App.setTitle('Index');
            var user = this.App.Data.User;
            var vars = {
                logged_in: !!user,
                app_name: Data.AppName
            };
            if(this.data) {
                vars.announcement = this.data.announcement;
                vars.failing_searches = this.data.failing_searches;
                vars.total_active_alerts = this.data.active_alerts[4] + this.data.active_alerts[5];
                vars.active_alerts = this.data.active_alerts;
                vars.stale_alerts = this.data.stale_alerts;
                vars.no_recent_cron = this.data.no_recent_cron;
            }
            this.$el.html(this.template(vars));

            if(this.data) {
                var chartdata = null;
                var cdata = null;
                var chart = null;

                chartdata = _.zip.apply(null, this.data.historical_alerts);
                cdata = {
                    labels: chartdata[0],
                    datasets: [_.extend({lineTension:0, data: chartdata[1], label: 'Created'}, ChartView.colors[0])]
                };
                chart = new ChartView(this.App, {
                    title: 'Alerts in the last 15 days', data: cdata
                });
                this.registerView(chart, true);

                chartdata = _.zip.apply(null, this.data.historical_actions[0]);
                cdata = {
                    labels: chartdata[0],
                    datasets: [
                        _.extend({lineTension:0, data: chartdata[1], label: 'Escalated'}, ChartView.colors[2]),
                        _.extend({lineTension:0, data: _.pluck(this.data.historical_actions[1], 1), label: 'Assigned'}, ChartView.colors[1]),
                        _.extend({lineTension:0, data: _.pluck(this.data.historical_actions[2], 1), label: 'Resolved'}, ChartView.colors[3]),
                    ]
                };
                chart = new ChartView(this.App, {
                    title: 'Actions in the last 15 days', data: cdata
                });
                this.registerView(chart, true);
            }

            this.App.hideLoader();
        }
    });

    return IndexView;
});
