"use strict";
define(function(require) {
    var Handlebars = require('handlebars');

    return {
        'index': Handlebars.compile(require('text!templatefiles/index.html')),
        'header': Handlebars.compile(require('text!templatefiles/header.html')),
        'footer': Handlebars.compile(require('text!templatefiles/footer.html')),
        'login': Handlebars.compile(require('text!templatefiles/login.html')),
        'logout': Handlebars.compile(require('text!templatefiles/logout.html')),
        'notfound': Handlebars.compile(require('text!templatefiles/notfound.html')),
        'forbidden': Handlebars.compile(require('text!templatefiles/forbidden.html')),
        'admin': Handlebars.compile(require('text!templatefiles/admin.html')),
        'settings': Handlebars.compile(require('text!templatefiles/settings.html')),
        'health': Handlebars.compile(require('text!templatefiles/health.html')),


        'message': Handlebars.compile(require('text!templatefiles/message.html')),
        'navbar': Handlebars.compile(require('text!templatefiles/navbar.html')),
        'help': Handlebars.compile(require('text!templatefiles/help.html')),
        'list': Handlebars.compile(require('text!templatefiles/list.html')),
        'table': Handlebars.compile(require('text!templatefiles/table.html')),
        'modal': Handlebars.compile(require('text!templatefiles/modal.html')),
        'chart': Handlebars.compile(require('text!templatefiles/chart.html')),

        // Searches
        'searches/searchlistentry': Handlebars.compile(require('text!templatefiles/searches/searchlistentry.html')),
        'searches/searchtableentry': Handlebars.compile(require('text!templatefiles/searches/searchtableentry.html')),
        'searches/searchmodal': Handlebars.compile(require('text!templatefiles/searches/searchmodal.html')),
        'searches/createmodal': Handlebars.compile(require('text!templatefiles/searches/createmodal.html')),

        'searches/changelogmodal': Handlebars.compile(require('text!templatefiles/searches/changelogmodal.html')),
        'searches/statisticsmodal': Handlebars.compile(require('text!templatefiles/searches/statisticsmodal.html')),
        'searches/jobsmodal': Handlebars.compile(require('text!templatefiles/searches/jobsmodal.html')),
        'element': Handlebars.compile(require('text!templatefiles/element.html')),
        'elementcompact': Handlebars.compile(require('text!templatefiles/elementcompact.html')),
        'searches/search/ping/a': Handlebars.compile(require('text!templatefiles/searches/search/ping/a.html')),
        'searches/search/http/a': Handlebars.compile(require('text!templatefiles/searches/search/http/a.html')),
        'searches/search/graphite/b': Handlebars.compile(require('text!templatefiles/searches/search/graphite/b.html')),
        'searches/search/threatexchange/a': Handlebars.compile(require('text!templatefiles/searches/search/threatexchange/a.html')),
        'searches/search/elasticsearch/b': Handlebars.compile(require('text!templatefiles/searches/search/elasticsearch/b.html')),
        'searches/search/elasticsearch/d': Handlebars.compile(require('text!templatefiles/searches/search/elasticsearch/d.html')),
        'searches/search/push/b': Handlebars.compile(require('text!templatefiles/searches/search/push/b.html')),
        'searches/search': Handlebars.compile(require('text!templatefiles/searches/search.html')),

        // Alerts
        'alerts/alertgroup': Handlebars.compile(require('text!templatefiles/alerts/alertgroup.html')),
        'alerts/sendmodal': Handlebars.compile(require('text!templatefiles/alerts/sendmodal.html')),
        'alerts/whitelistmodal': Handlebars.compile(require('text!templatefiles/alerts/whitelistmodal.html')),
        'alerts/assignmodal': Handlebars.compile(require('text!templatefiles/alerts/assignmodal.html')),
        'alerts/resolvemodal': Handlebars.compile(require('text!templatefiles/alerts/resolvemodal.html')),
        'alerts/actionmodal': Handlebars.compile(require('text!templatefiles/alerts/actionmodal.html')),
        'alerts/searchmodal': Handlebars.compile(require('text!templatefiles/alerts/searchmodal.html')),
        'alerts/alertgroups': Handlebars.compile(require('text!templatefiles/alerts/alertgroups.html')),
        'alerts/queryentry': Handlebars.compile(require('text!templatefiles/alerts/queryentry.html')),

        'alerts/actions': Handlebars.compile(require('text!templatefiles/alerts/actions.html')),
        'alerts/alertentry': Handlebars.compile(require('text!templatefiles/alerts/alertentry.html')),
        'alerts/alertentryfield': Handlebars.compile(require('text!templatefiles/alerts/alertentryfield.html')),
        'alerts/alertfield': Handlebars.compile(require('text!templatefiles/alerts/alertfield.html')),
        'alerts/changelogentry': Handlebars.compile(require('text!templatefiles/alerts/changelogentry.html')),
        'alerts/alert': Handlebars.compile(require('text!templatefiles/alerts/alert.html')),

        // Users
        'users/userentry': Handlebars.compile(require('text!templatefiles/users/userentry.html')),

        'users/user': Handlebars.compile(require('text!templatefiles/users/user.html')),

        // Groups
        'groups/groupentry': Handlebars.compile(require('text!templatefiles/groups/groupentry.html')),

        'groups/grouptarget': Handlebars.compile(require('text!templatefiles/groups/grouptarget.html')),
        'groups/group': Handlebars.compile(require('text!templatefiles/groups/group.html')),

        // Reports
        'reports/reportentry': Handlebars.compile(require('text!templatefiles/reports/reportentry.html')),

        'reports/reporttarget': Handlebars.compile(require('text!templatefiles/reports/reporttarget.html')),
        'reports/report': Handlebars.compile(require('text!templatefiles/reports/report.html')),

        // Lists
        'lists/listentry': Handlebars.compile(require('text!templatefiles/lists/listentry.html')),

        'lists/listinfo': Handlebars.compile(require('text!templatefiles/lists/listinfo.html')),
        'lists/list': Handlebars.compile(require('text!templatefiles/lists/list.html')),

        // Renderers
        'renderer/table': Handlebars.compile(require('text!templatefiles/renderer/table.html')),
    };
});
