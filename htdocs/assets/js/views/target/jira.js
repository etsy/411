"use strict";
define(function(require) {
    var _ = require('underscore'),
        TargetView = require('views/target'),
        Data = require('data'),
        Templates = require('templates'),
        Util = require('util');


    var JiraTargetView = TargetView.extend({
        __render: function() {
            if(!Data.Jira) {
                var view = this;

                this.App.showLoader();
                require(['text!/api/jira'], function(data) {
                    view.App.hideLoader();
                    Data.Jira = JSON.parse(data);
                    view.___render();
                });
            } else {
                this.___render();
            }
        },
        ___render: function() {
            var projects = Data.Jira;
            var issuetypes = {};

            var project_search = function(q) {
                var results = [];
                for(var k in projects) {
                    var project = projects[k];
                    if(project.name.toLowerCase().indexOf(q.term) !== -1) {
                        results.push({id: k, text: project.name});
                    }
                }

                q.callback({results: results});
            };
            var project_init = function(elem, callback) {
                var k = elem.val();
                if(projects[k]) {
                    issuetypes = projects[k].issuetypes;
                    callback({id: k, text: projects[k].name});
                }
            };

            var issuetype_search = function(q) {
                var results = [];
                for(var k in issuetypes) {
                    var issuetype = issuetypes[k];
                    if(issuetype.name.toLowerCase().indexOf(q.term) !== -1) {
                        results.push({id: k, text: issuetype.name});
                    }
                }

                q.callback({results: results});
            };
            var issuetype_init = function(elem, callback) {
                var k = elem.val();
                if(k in issuetypes) {
                    callback({id: k, text: issuetypes[k].name});
                }
            };

            // Update the available list of issuetypes.
            var project_select = this.registerElement('input[name=project]');
            var issuetype_select = this.registerElement('input[name=type]');
            project_select.on('change', function(e) {
                issuetypes = e.added ? projects[e.added.id].issuetypes:[];
                issuetype_select.select2('val', '');
            });

            Util.initSelect(project_select, {
                initSelection: project_init,
                query: project_search
            }, true);
            Util.initSelect(issuetype_select, {
                initSelection: issuetype_init,
                query: issuetype_search
            }, true);
        }
    });

    return JiraTargetView;
});
