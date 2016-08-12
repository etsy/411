"use strict";
/**
 * Application entrypoint
 * Initialize the requirejs config and start the application
 */

// Root directory config. Used to generate links and load data.
var DOC_ROOT = '/';
var ASSET_ROOT = '/assets/';
var API_ROOT = '/api/';

require.config({
    baseUrl: ASSET_ROOT + 'js/',
    paths: {
        text: 'libs/text',
        jquery: 'libs/jquery',
        purl: 'libs/purl',
        uri: 'libs/uri',
        underscore: 'libs/underscore',
        backbone: 'libs/backbone',
        bootstrap: 'libs/bootstrap',
        routefilter: 'libs/backbone.routefilter',
        handlebars: 'libs/handlebars',
        select2: 'libs/select2',
        tablesorter: 'libs/tablesorter',
        moment: 'libs/moment',
        dragula: 'libs/dragula',
        autosize: 'libs/autosize',
        mousetrap: 'libs/mousetrap',
        datetimepicker: 'libs/datetimepicker',
        codemirror: 'libs/codemirror',
        chartjs: 'libs/chart',
        templatefiles: '../templates',
        data_json: API_ROOT + 'data',
    },
    map: {
        uri: {
            punycode: 'libs/false',
            IPv6: 'libs/false',
            SecondLevelDomains: 'libs/false'
        }
    },
    shim: {
        tablesorter: {
            deps: ['jquery']
        },
        bootstrap: {
            deps: ['jquery']
        },
        autosize: {
            deps: ['jquery']
        },
        select2: {
            deps: ['jquery'],
            exports: 'Select2'
        }
    },
    waitSeconds: 15,
});

require(['app'], function(App) {
    var app = new App();
    app.start();

    // Store a reference to the app for scripting.
    window.Foo = app;
});
