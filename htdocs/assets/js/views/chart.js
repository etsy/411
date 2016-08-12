"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        Chart = require('chartjs'),
        Templates = require('templates');

    var colors = [
        { // blue
            backgroundColor: "rgba(151,187,205,0.2)",
            borderColor: "rgba(151,187,205,1)",
            pointBackgroundColor: "rgba(151,187,205,1)",
            pointBorderColor: "#fff",
            pointHoverBackgroundColor: "#fff",
            pointHoverBorderColor: "rgba(151,187,205,0.8)"
        },
        { // light grey
            backgroundColor: "rgba(220,220,220,0.2)",
            borderColor: "rgba(220,220,220,1)",
            pointBackgroundColor: "rgba(220,220,220,1)",
            pointBorderColor: "#fff",
            pointHoverBackgroundColor: "#fff",
            pointHoverBorderColor: "rgba(220,220,220,0.8)"
        },
        { // red
            backgroundColor: "rgba(247,70,74,0.2)",
            borderColor: "rgba(247,70,74,1)",
            pointBackgroundColor: "rgba(247,70,74,1)",
            pointBorderColor: "#fff",
            pointHoverBackgroundColor: "#fff",
            pointHoverBorderColor: "rgba(247,70,74,0.8)"
        },
        { // green
            backgroundColor: "rgba(70,191,189,0.2)",
            borderColor: "rgba(70,191,189,1)",
            pointBackgroundColor: "rgba(70,191,189,1)",
            pointBorderColor: "#fff",
            pointHoverBackgroundColor: "#fff",
            pointHoverBorderColor: "rgba(70,191,189,0.8)"
        },
        { // yellow
            backgroundColor: "rgba(253,180,92,0.2)",
            borderColor: "rgba(253,180,92,1)",
            pointBackgroundColor: "rgba(253,180,92,1)",
            pointBorderColor: "#fff",
            pointHoverBackgroundColor: "#fff",
            pointHoverBorderColor: "rgba(253,180,92,0.8)"
        },
        { // grey
            backgroundColor: "rgba(148,159,177,0.2)",
            borderColor: "rgba(148,159,177,1)",
            pointBackgroundColor: "rgba(148,159,177,1)",
            pointBorderColor: "#fff",
            pointHoverBackgroundColor: "#fff",
            pointHoverBorderColor: "rgba(148,159,177,0.8)"
        },
        { // dark grey
            backgroundColor: "rgba(77,83,96,0.2)",
            borderColor: "rgba(77,83,96,1)",
            pointBackgroundColor: "rgba(77,83,96,1)",
            pointBorderColor: "#fff",
            pointHoverBackgroundColor: "#fff",
            pointHoverBorderColor: "rgba(77,83,96,1)"
        }
    ];

    var ChartView = View.extend({
        title: '',
        tagName: 'div',
        template: Templates['chart'],
        data: null,
        chart: null,
        initialize: function(options) {
            this.title = options.title;
            this.data = options.data;
        },
        _render: function() {
            this.$el.html(this.template({title: this.title}));
            var ctx = this.$('.chart')[0].getContext('2d');

            // FIXME: View could get destroyed before this triggers.
            _.defer($.proxy(function() {
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: this.data,
                    multiTooltipTemplate: "<%= value %> <%= datasetLabel %>"
                });
            }, this));
        },
        destroy: function() {
            if(this.chart) {
                this.chart.destroy();
            }
            View.prototype.destroy.call(this);
        }
    }, {
        colors: colors
    });

    return ChartView;
});
