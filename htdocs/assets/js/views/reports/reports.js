"use strict";
define(function(require) {
    var View = require('view'),
        NavbarView = require('views/navbar'),
        TableView = require('views/table'),
        Templates = require('templates'),

        Report = require('models/report');


    var ReportsNavbarView = NavbarView.extend({
        title: 'Reports',
        sidelinks: [
            {name: 'Create', icon: 'file', link: '/reports/new', action: 'create'}
        ],
        _render: function() {
            NavbarView.prototype._render.call(this);

            this.App.registerSelectableKbdShortcut('p', 'pdf', 'Generate PDF for current item', false);
            this.App.registerSelectableKbdShortcut('v', 'csv', 'Generate CSV for current item', false);
        }
    });

    var ReportEntryView = View.extend({
        tagName: 'tr',
        template: Templates['reports/reportentry'],
        events: {
            'click .generate-pdf-button': 'generatePDF',
            'click .generate-csv-button': 'generateCSV',
        },
        _render: function() {
            var vars = this.model.toJSON();
            vars.types = Report.Data().Types;
            this.$el.html(this.template(vars));
        },
        selectAction: function(action) {
            switch(action) {
                case 'open': this.open(); break;
                case 'pdf': this.generatePDF(); break;
                case 'csv': this.generateCSV(); break;
            }
        },
        open: function() {
            this.$('a')[0].click();
        },
        generatePDF: function() {
            var data = {_nonce: this.App.Data.Nonce};
            this.model.generate(0, data);
        },
        generateCSV: function() {
            var data = {_nonce: this.App.Data.Nonce};
            this.model.generate(1, data);
        }
    });

    var ReportsTableView = TableView.extend({
        subView: ReportEntryView,
        selectable: true,
        columns: [
            {name: '', sorter: 'false'},
            {name: '', sorter: 'false'},
            {name: '', sorter: 'false'},
            {name: 'Name', width: 80},
            {name: 'Type', width: 10},
            {name: 'Enabled'},
        ]
    });

    /**
     * The reports View
     */
    var ReportsView = View.extend({
        _load: function() {
            this.loadCollections([this.App.Data.Users]);
        },
        _render: function() {
            this.App.setTitle('Reports');
            this.registerView(new ReportsNavbarView(this.App), true);
            this.registerView(new ReportsTableView(
                this.App, {collection: this.App.Data.Reports}
            ), true);

            this.App.hideLoader();
        }
    });

    return ReportsView;
});
