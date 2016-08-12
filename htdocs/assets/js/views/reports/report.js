"use strict";
define(function(require) {
    var _ = require('underscore'),
        NavbarView = require('views/navbar'),
        ModelView = require('views/model'),
        ListView = require('views/list'),
        SearchView = require('views/searches/search'),
        Templates = require('templates'),
        Util = require('util'),

        Report = require('models/report'),
        ReportTarget = require('models/reporttarget'),
        ReportTargetCollection = require('collections/reporttarget');


    var ReportNavbarView = NavbarView.extend({
        title: 'Report',
    });

    var ReportTargetEntryView = ModelView.extend({
        tagName: 'li',
        template: Templates['reports/reporttarget'],
        events: {
            'click .up-button': 'up',
            'click .down-button': 'down',
            'click .close-button': 'delete',
        },
        _render: function() {
            var search = this.App.Data.Searches.get(this.model.get('search_id'));
            this.$el.html(this.template({name: search.get('name')}));
        },
        up: function() {
            this.trigger('up', this);
        },
        down: function() {
            this.trigger('down', this);
        },
        delete: function() {
            this.trigger('delete', this);
        }
    });

    var ReportTargetsListView = ListView.extend({
        title: 'Targets',
        subView: ReportTargetEntryView,
        help: 'Select a search to add it to the Report.',
        events: {
            'change .select': 'create',
        },
        _render: function() {
            ListView.prototype._render.call(this);

            // Initialize the target select.
            Util.initSearchSelect(
                this.registerElement('.select'),
                this.App.Data.Searches,
                {placeholder: ''}
            );
        },
        initializeSubView: SearchView.ElementsListView.prototype.initializeSubView,
        create: function(e) {
            var val = e.target.value;
            if(_.isUndefined(val)) {
                return;
            }

            var search_id = parseInt(val, 10);
            this.$('.select').select2('val', '');

            // Reset positions if necessary. This cleans up any gaps from deletes.
            var models = this.collection.models;
            for(var i = 0; i < models.length; ++i) {
                models[i].set('position', i);
            }
            // Populate all the necessary information for this model.
            this.addModel(new ReportTarget({
                report_id: this.collection.id,
                search_id: search_id,
                position: models.length
            }));
        },
        moveModel: SearchView.ElementsListView.prototype.moveModel
    });

    /**
     * The report View
     */
    var ReportView = ModelView.extend({
        modelName: 'Report',
        modelClass: Report,
        modelUrl: '/report/',

        events: {
            'change #type-select': 'toggleTargets',
            'click #generate-pdf-button': 'generatePDF',
            'click #generate-csv-button': 'generateCSV',
            'click #create-button': 'processSave',
            'click #update-button': 'processSave',
            'click #delete-button': 'showDelete',
        },
        template: Templates['reports/report'],
        _load: function(id) {
            this.collection = new ReportTargetCollection([], {id: id});

            // Only fetch the collection if we're looking at an existing report.
            var deferred = [];
            if(id) {
                deferred.push(this.collection.update());
            }

            this.loadCollectionsAndModel(
                [this.App.Data.Users, this.App.Data.Groups, this.App.Data.Searches],
                this.App.Data.Reports, id,
                undefined,
                deferred
            );
        },
        _render: function() {
            this.App.setTitle('Report: ' + (this.model.isNew() ? 'New':this.model.get('id')));
            this.registerView(new ReportNavbarView(this.App), true);

            var vars = this.model.toJSON();
            _.extend(vars, {
                new_report: this.model.isNew(),
                types: Report.Data().Types
            });

            this.$el.append(this.template(vars));
            // Only render the list if the model is saved.
            if(!this.model.isNew()) {
                this.registerView(
                    new ReportTargetsListView(this.App, {collection: this.collection}),
                    true, this.$('.target-list')
                );
            }

            Util.initAssigneeSelect(
                this.registerElement('input[name=assignee]'),
                this.App.Data.Users, this.App.Data.Groups
            );

            this.registerElement('.time-select').datetimepicker({
                useSeconds: true,
                format: 'ddd, DD MMM YYYY HH:mm:ss [GMT]',
            });

            this.toggleTargets();
            this.detectChanges();

            this.App.hideLoader();
        },
        toggleTargets: function() {
            var type = parseInt(this.$('#type-select').val(), 10);
            this.$('.target-list').toggleClass('hidden', !type);
        },
        readForm: function() {
            var form = this.$('#report-form');
            var data = Util.serializeForm(form);

            data.start_date = Util.getTimestamp(data.start_date);
            data.enabled = !!data.enabled;

            // Parse the assignee field.
            var assignee = Util.parseAssignee(data.assignee);
            data.assignee_type = assignee[0];
            data.assignee = assignee[1];

            return data;
        },
        generatePDF: function() {
            return this._generate(0);
        },
        generateCSV: function() {
            return this._generate(1);
        },
        _generate: function(type) {
            var data = this.readForm();
            data._nonce = this.App.Data.Nonce;

            this.model.generate(type, data);
            return false;
        },
        processSave: function() {
            var data = this.readForm();

            if(!this.model.isNew()) {
                this.collection.save();
            }
            this.saveModel(data);
            return false;
        },
        /**
         * Show the delete modal.
         */
        showDelete: function() {
            var view = this.App.setModal(new ModelView.DeleteModalView(this.App, this.modelName));
            this.listenTo(view, 'button:delete', this.destroyModel);
        },
        /**
         * Delete this model and redirect to the reports page.
         */
        destroyModel: function() {
            ModelView.prototype.destroyModel.call(this, '/reports');
        }
    });

    return ReportView;
});
