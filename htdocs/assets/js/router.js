"use strict";
define(function(require) {
    var Backbone = require('backbone'),

        IndexView = require('views/index'),
        LoginView = require('views/login'),
        LogoutView = require('views/logout'),
        ForbiddenView = require('views/forbidden'),
        NotFoundView = require('views/notfound'),

        AdminView = require('views/admin'),
        SettingsView = require('views/settings'),
        HealthView = require('views/health'),

        SearchView = require('views/searches/search'),
        SearchesView = require('views/searches/searches'),

        AlertView = require('views/alerts/alert'),
        AlertsView = require('views/alerts/alerts'),
        AlertsFeedView = require('views/alerts/alertsfeed'),

        UserView = require('views/users/user'),
        UsersView = require('views/users/users'),

        GroupView = require('views/groups/group'),
        GroupsView = require('views/groups/groups'),

        ReportView = require('views/reports/report'),
        ReportsView = require('views/reports/reports'),

        ListView = require('views/lists/list'),
        ListsView = require('views/lists/lists');


    /**
     * The Router class.
     * Routes request to the correct view.
     */
    var Router = Backbone.Router.extend({
        // Route declarations.
        routes: {
            'search/:id':           'search',
            'searches/new':         'new_search',
            'searches':             'searches',

            'alerts/feed':          'alerts_feed',
            'alerts':               'alerts',
            'alert/:id':            'alert',

            'group/:id':            'group',
            'groups/new':           'new_group',
            'groups':               'groups',

            'report/:id':            'report',
            'reports/new':           'new_report',
            'reports':               'reports',

            'user/:id':             'user',
            'users/new':            'new_user',
            'users':                'users',

            'list/:id':             'list',
            'lists/new':            'new_list',
            'lists':                'lists',

            'login':                'login',
            'logout':               'logout',

            'admin':                'admin',
            'settings':             'settings',
            'health':               'health',

            '':                     'index',
            'forbidden':            'forbidden',
            '*actions':             'notfound',
        },

        App: null,

        /**
         * Initialize.
         */
        initialize: function(app) {
            this.App = app;
        },

        /**
         * Set up to do before triggering a route.
         */
        before: function(route) {
            return this.App.beforeRoute(route);
        },

        // Route definitions.
        search: function(id) {
            this.App.loadView(new SearchView(this.App), [false, id]);
        },
        new_search: function() {
            this.App.loadView(new SearchView(this.App), [true]);
        },
        searches: function() {
            this.App.loadView(new SearchesView(this.App));
        },

        alert: function(id) {
            this.App.loadView(new AlertView(this.App), [id]);
        },
        alerts_feed: function() {
            this.App.loadView(new AlertsFeedView(this.App));
        },
        alerts: function() {
            this.App.loadView(new AlertsView(this.App));
        },

        group: function(id) {
            this.App.loadView(new GroupView(this.App), [id]);
        },
        new_group: function() {
            this.App.loadView(new GroupView(this.App));
        },
        groups: function() {
            this.App.loadView(new GroupsView(this.App));
        },

        report: function(id) {
            this.App.loadView(new ReportView(this.App), [id]);
        },
        new_report: function() {
            this.App.loadView(new ReportView(this.App));
        },
        reports: function() {
            this.App.loadView(new ReportsView(this.App));
        },

        users: function() {
            this.App.loadView(new UsersView(this.App));
        },
        new_user: function() {
            this.App.loadView(new UserView(this.App));
        },
        user: function(id) {
            this.App.loadView(new UserView(this.App), [id]);
        },

        list: function(id) {
            this.App.loadView(new ListView(this.App), [id]);
        },
        new_list: function() {
            this.App.loadView(new ListView(this.App));
        },
        lists: function() {
            this.App.loadView(new ListsView(this.App));
        },

        login: function() {
            this.App.loadView(new LoginView(this.App));
        },
        logout: function() {
            this.App.loadView(new LogoutView(this.App));
        },

        admin: function() {
            this.App.loadView(new AdminView(this.App));
        },

        settings: function() {
            this.App.loadView(new SettingsView(this.App));
        },

        health: function() {
            this.App.loadView(new HealthView(this.App));
        },

        index: function() {
            this.App.loadView(new IndexView(this.App));
        },

        forbidden: function(actions) {
            this.App.loadView(new ForbiddenView(this.App));
        },
        notfound: function(actions) {
            this.App.loadView(new NotFoundView(this.App));
        }
    });

    return Router;
});
