"use strict";
define(function(require) {
    var SearchView = require('views/searches/search'),
        NullSearchView = require('views/searches/search/null'),
        PingSearchView = require('views/searches/search/ping'),
        HTTPSearchView = require('views/searches/search/http'),
        GraphiteSearchView = require('views/searches/search/graphite'),
        ThreatexchangeSearchView = require('views/searches/search/threatexchange'),
        ElasticsearchSearchView = require('views/searches/search/elasticsearch'),
        PushSearchView = require('views/searches/search/push'),
        ECLSearchView = require('views/searches/search/ecl');


    /**
     * Loader for SearchView subclasses.
     */
    SearchView.registerSubclass('null', NullSearchView);
    SearchView.registerSubclass('ping', PingSearchView);
    SearchView.registerSubclass('http', HTTPSearchView);
    SearchView.registerSubclass('graphite', GraphiteSearchView);
    SearchView.registerSubclass('threatexchange', ThreatexchangeSearchView);
    SearchView.registerSubclass('logstash', ElasticsearchSearchView);
    SearchView.registerSubclass('alert', ElasticsearchSearchView);
    SearchView.registerSubclass('push', PushSearchView);
    SearchView.registerSubclass('ecl', ECLSearchView);
});
