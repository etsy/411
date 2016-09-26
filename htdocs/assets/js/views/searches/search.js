"use strict";
define(function(require) {
    var _ = require('underscore'),
        Dragula = require('dragula'),
        NavbarView = require('views/navbar'),
        ModalView = require('views/modal'),
        ModelView = require('views/model'),
        ListView = require('views/list'),
        AlertGroupView = require('views/alerts/alertgroup'),
        FilterView = require('views/filter'),
        TargetView = require('views/target'),
        ClassMap = require('classmap'),
        Templates = require('templates'),
        Util = require('util'),
        URI = require('uri'),
        Data = require('data'),

        Search = require('models/search'),
        Filter = require('models/filter'),
        Target = require('models/target'),
        AlertCollection = require('collections/alert'),
        SearchLogCollection = require('collections/searchlog'),
        FilterCollection = require('collections/filter'),
        TargetCollection = require('collections/target');


    var ResultsModalView = ModalView.extend({
        title: 'Results',
        large: true,
        subTemplate: _.constant(''),
        events: {
            'click a': 'showAlert',
        },
        showAlert: function(e) {
            this.hide($.proxy(function() {
                var link = new URI(e.currentTarget.href);
                this.App.Router.navigate(link.resource(), {trigger: true});
            }, this));
            return false;
        },
        _render: function() {
            ModalView.prototype._render.call(this);
            var view  = new AlertGroupView(this.App, {
                collection: this.collection,
                preview: true,
            });
            this.registerView(view, true, this.$('.modal-body'));
        }
    });

    var StatisticsModalView = ModalView.extend({
        title: 'Statistics',
        subTemplate: Templates['searches/statisticsmodal'],
        _load: function() {
            this.model.getStats({
                success: this.cbLoaded(function(resp) {
                    this.vars = {stats: resp};
                    this.render();
                })
            });
        }
    });

    var JobsModalView = ModalView.extend({
        title: 'Jobs',
        subTemplate: Templates['searches/jobsmodal'],
        large: true,
        _load: function() {
            this.model.getJobs({
                data: {
                    reverse: true
                },
                success: this.cbLoaded(function(resp) {
                    this.vars = {
                        jobs: resp,
                        states: Data.Job.States
                    };
                    this.render();
                })
            });
        }
    });

    /**
     * View for displaying all the modifications made to the Search.
     * We could use a TableView here, but it's easier to throw everything in a
     * single template.
     */
    var ChangelogModalView = ModalView.extend({
        title: 'Changelog',
        large: true,
        subTemplate: Templates['searches/changelogmodal'],
        events: {
            'click .view-button': 'hideAndView',
        },
        _load: function() {
            this.collection = new SearchLogCollection([], {id: this.model.get('id')});
            this.loadCollections([this.collection], function(resp) {
                var users = this.App.Data.Users;
                var logs = this.collection.map(function(x) {
                    var log = x.pick(['description', 'create_date', 'id']);
                    log.user = Util.getUserName(x.get('user_id'), users);
                    return log;
                });
                this.vars = {logs: logs};
                this.render();
            });
        },
        /**
         * Trigger a viewlog event once the modal is hidden.
         */
        hideAndView: function(e) {
            var searchlog_id = parseInt(e.currentTarget.value, 10);

            // Once the modal is hidden.
            this.hide($.proxy(function() {
                var searchlog = this.collection.get(searchlog_id);
                this.App.Bus.trigger('viewlog', searchlog);
            }, this));
            return false;
        }
    });

    var SearchNavbarView = NavbarView.extend({
        title: 'Search',
        events: {
            'click .stats-button': 'stats',
            'click .changelog-button': 'changelog',
            'click .jobs-button': 'jobs',
            'click .alerts-button': 'gotoAlerts',
        },

        initialize: function() {
            NavbarView.prototype.initialize.call(this);
            if(this.model.isNew()) {
                return;
            }

            this.links = [
                {name: 'Statistics', action: 'stats'},
                {name: 'Changelog', action: 'changelog'},
                {name: 'Jobs', action: 'jobs'},
            ];
            this.sidelinks = [
                {name: 'Go to Alerts', action: 'alerts', icon: 'flash'}
            ];
        },
        // Pass these events back to the parent view via the bus.
        stats: function() {
            this.App.Bus.trigger('stats');
        },
        changelog: function() {
            this.App.Bus.trigger('changelog');
        },
        jobs: function() {
            this.App.Bus.trigger('jobs');
        },
        gotoAlerts: function() {
            this.App.Router.navigate('/alerts?' + decodeURIComponent($.param({
                query: 'state:(0 1) AND search_id:' + this.model.id
            })), {trigger: true});
        }
    });

    var ElementEditModalView = ModalView.extend({
        subTemplate: _.constant(''),
        subView: null,
        buttons: [
            {action: 'save', type: 'success', icon: 'floppy-disk', name: 'Save', persist: true}
        ],
        events: {
            'click .save-button': 'write'
        },
        _render: function() {
            ModalView.prototype._render.call(this);
            var viewClass = this.subView.getSubclass(this.model.get('type'));
            var view = new viewClass(this.App, {model: this.model});
            view.hide_chrome = true;
            view.load();
            this.registerView(view, false, undefined, 'view');
            this.$('.modal-body').append(view.el);
        },
        write: function() {
            var view = this.getView('view');
            var data = view.readForm();
            var button = this.$el.find('.save-button');

            button.attr('disabled', true);
            this.model.validate(data, {
                success: $.proxy(function() {
                    this.model.set(data);
                    this.hide();
                }, this),
                complete: function() {
                    button.attr('disabled', false);
                }
            });
            return false;
        }
    });

    var FilterEditModalView = ElementEditModalView.extend({
        title: 'Filter',
        subView: FilterView,
    });

    var TargetEditModalView = ElementEditModalView.extend({
        title: 'Target',
        subView: TargetView,
    });

    var ElementsListView = ListView.extend({
        hiddenForm: true,
        events: {
            'change .select': 'create',
        },

        // Class to construct.
        subClass: null,
        // Edit view.
        editView: null,
        // Function to construct the select element.
        selectInit: null,

        _render: function() {
            this.$el.addClass('col-md-6 col-xs-12');
            ListView.prototype._render.call(this);

            this.selectInit(
                this.registerElement('.select'),
                this.subClass.Data().Types
            );
        },
        initializeSubView: function(model) {
            var view = ListView.prototype.initializeSubView.call(this, model, {compact: true});

            this.listenTo(view, 'up', _.partial(this.moveModel, _, true));
            this.listenTo(view, 'down', _.partial(this.moveModel, _, false));
            this.listenTo(view, 'edit', _.partial(this.editModel, model));
            return view;
        },
        create: function(e) {
            var type = e.currentTarget.value;
            if(_.isUndefined(type)) {
                return;
            }
            $(e.currentTarget).select2('val', '');

            // Reset positions if necessary. This cleans up any gaps from deletes.
            var models = this.collection.models;
            for(var i = 0; i < models.length; ++i) {
                models[i].set('position', i);
            }
            var model = new this.subClass({
                type: type, search_id: this.model.get('id'), position: models.length
            });
            this.addModel(model);
            this.editModel(model);
        },
        editModel: function(model) {
            var view = this.App.setModal(new this.editView(this.App, {model: model}));
        },
        moveModel: function(v, up) {
            // Loop over all the models and look for the current one.
            var models = this.collection.models;
            var x = models.indexOf(v.model);
            if(x === -1) {
                return;
            }
            // Apply the delta.
            var y = x + (up ? -1:1);
            if(y < 0 || y >= models.length) {
                return;
            }

            var tmp = models[x].get('position');
            models[x].set('position', models[y].get('position'));
            models[y].set('position', tmp);
            this.collection.sort();

            var el = $(v.el);
            if (up && el.not(':first-child'))
                el.prev().before(el);
            if (!up && el.not(':last-child'))
                el.next().after(el);

            this.trigger('change');
        },
        processSave: function() {
            return this.collection.save();
        }
    });

    var FiltersListView = ElementsListView.extend({
        title: 'Filters',
        subClass: Filter,
        subView: FilterView,
        editView: FilterEditModalView,
        help: 'Select a filter type to add it to the list and configure it.',
        selectInit: Util.initFilterSelect,
    });

    var TargetsListView = ElementsListView.extend({
        title: 'Targets',
        subClass: Target,
        subView: TargetView,
        editView: TargetEditModalView,
        help: 'Select a target type to add it to the list and configure it.',
        selectInit: Util.initTargetSelect,
    });

    /**
     * The search View
     */
    var SearchView = ModelView.extend({
        modelName: 'Search',
        modelClass: Search,
        modelUrl: '/search/',

        // Whether to hide the query field.
        no_query: false,
        // Whether to hide the range field.
        no_range: false,
        // Whether to hide the frequency field.
        no_freq: false,
        // Additional content to insert. Check out search.html for details.
        addnFieldsATpl: _.constant(''),
        addnFieldsBTpl: _.constant(''),
        addnFieldsCTpl: _.constant(''),
        addnFieldsDTpl: _.constant(''),
        addnFieldsETpl: _.constant(''),
        addnFieldsFTpl: _.constant(''),

        draggable: null,

        events: {
            'click #schedule-checkbox': 'toggleSchedule',
            'click #notif-checkbox': 'toggleNotif',
            'click #autoclose-checkbox': 'toggleAutoclose',
            'click #save-elements-button': 'processSaveElements',
            'click #test-button': 'processTest',
            'click #execute-button': 'processExecute',
            'click #create-button': 'processSave',
            'click #update-button': 'processSave',
            'click #delete-button': 'showDelete',
        },
        template: Templates['searches/search'],
        _load: function() {
            this.filters = new FilterCollection([], {id: this.model.id});
            this.targets = new TargetCollection([], {id: this.model.id});

            // Only fetch the collections if we're looking at an existing search.
            var deferred = [];
            if(this.model.id) {
                deferred.push(this.filters.update());
                deferred.push(this.targets.update());
            }

            $.when.apply($, deferred).then($.proxy(this.render, this));
        },
        _render: function() {
            this.App.setTitle('Search: ' + (this.model.isNew() ? 'New':this.model.get('id')));
            this.registerView(new SearchNavbarView(this.App, {model: this.model}), true);
            this.listenTo(this.App.Bus, 'stats', this.showStatistics);
            this.listenTo(this.App.Bus, 'changelog', this.showChangelog);
            this.listenTo(this.App.Bus, 'jobs', this.showJobs);
            this.listenTo(this.App.Bus, 'viewlog',  this.loadLog);

            var vars = this.model.toJSON();
            _.extend(vars, {
                new_search: this.model.isNew(),
                types: Search.Data().Types,
                priorities: Search.Data().Priorities,
                categories: Search.Data().Categories,
                notif_types: Search.Data().NotifTypes,
                notif_formats: Search.Data().NotifFormats,
                no_query: this.no_query,
                no_range: this.no_range,
                no_freq: this.no_freq,
                host: Data.Host,
            });
            // Render any additional content.
            vars.addn_fields_a = this.addnFieldsATpl(vars);
            vars.addn_fields_b = this.addnFieldsBTpl(vars);
            vars.addn_fields_c = this.addnFieldsCTpl(vars);
            vars.addn_fields_d = this.addnFieldsDTpl(vars);
            vars.addn_fields_e = this.addnFieldsETpl(vars);
            vars.addn_fields_f = this.addnFieldsFTpl(vars);
            this.$el.append(this.template(vars));

            this.__render();

            // Only render the list if the model is saved.
            if(!this.model.isNew()) {
                this.registerView(
                    new FiltersListView(this.App, {collection: this.filters, model: this.model}),
                    true, this.$('#filter-list'), 'filters'
                );
                this.registerView(
                    new TargetsListView(this.App, {collection: this.targets, model: this.model}),
                    true, this.$('#target-list'), 'targets'
                );
            }

            // Initialize selects.
            var tag_elems = this.registerElement('.tags');
            Util.initTags(tag_elems);

            var fields_elem = this.registerElement('ul.select2-choices');
            this.draggable = Dragula([fields_elem[0]], {
                orientation: 'horizontal',
            })
                .on('drag', $.proxy(function() {
                    tag_elems.select2("onSortStart");
                }, this))
                .on('drop', $.proxy(function() {
                    tag_elems.select2("onSortEnd");
                }, this));

            Util.initAssigneeSelect(
                this.registerElement('input[name=assignee]'),
                this.App.Data.Users, this.App.Data.Groups, false
            );
            Util.initUserSelect(
                this.registerElement('input[name=owner]'),
                this.App.Data.Users, false
            );
            Util.initTimeSelect(
                this.registerElement('input[name=range]')
            );
            Util.initTimeSelect(
                this.registerElement('input[name=autoclose_threshold]')
            );
            var freq_elem = this.registerElement('input[name=frequency]');
            if(freq_elem.length) {
                Util.initTimeSelect(freq_elem);
                this.toggleSchedule({currentTarget: this.$('input[name=schedule_type]')[0]});
            }

            this.toggleNotif({currentTarget: this.$('input[name=notif_enabled]')[0]});
            this.toggleAutoclose({currentTarget: this.$('input[name=autoclose_enabled]')[0]});

            this.detectChanges();

            this.App.hideLoader();
        },
        // Additional rendering logic.
        __render: function() {},
        toggleSchedule: function(e) {
            var checked = e.currentTarget.checked;
            this.$('.frequency-input').toggleClass('hidden', checked);
            this.$('.cron-input').toggleClass('hidden', !checked);
            this.$('.frequency-label').toggleClass('hidden', checked);
            this.$('.cron-label').toggleClass('hidden', !checked);
        },
        toggleNotif: function(e) {
            var checked = e.currentTarget.checked;
            this.$('.notif-format').toggleClass('hidden', !checked);
            this.$('select[name=notif_type]').attr('disabled', !checked);
        },
        toggleAutoclose: function(e) {
            var checked = e.currentTarget.checked;
            this.$('input[name=autoclose_threshold]').select2('enable', checked);
        },
        _unrender: function() {
            this.draggable.destroy();
        },
        readForm: function() {
            var form = this.$('#search-form');
            var data = Util.serializeForm(form);

            // schedule_type is an int.
            data.schedule_type = parseInt(data.schedule_type) || 0;

            // Parse out the tags.
            data.tags = data.tags.split(',');
            if(data.tags.length === 1 && data.tags[0] === '') {
                data.tags = [];
            }

            data.notif_type = parseInt(data.notif_type, 10);
            if(!data.notif_enabled) {
                data.notif_type = 0;
            }

            data.notif_data = [];

            data.autoclose_threshold = parseInt(data.autoclose_threshold, 10);
            if(!data.autoclose_enabled) {
                data.autoclose_threshold = 0;
            }

            // Store query data into a special object.
            data.query_data = {};
            if('query' in data) {
                data.query_data.query = data.query;
                delete data.query;
            }

            // Extract source_expr.
            if('source_expr' in data) {
                data.query_data.source_expr = data.source_expr;
                delete data.source_expr;
            }

            // Parse the assignee field.
            var assignee = Util.parseAssignee(data.assignee);
            data.assignee_type = assignee[0];
            data.assignee = assignee[1];

            data.owner = parseInt(data.owner, 10) || 0;

            data.enabled = !!data.enabled;

            // If the model is new, ship the type as well.
            if(this.model.isNew()) {
                data.type = this.model.get('type');
            }

            return data;
        },
        /**
         * Process the form and test the Search.
         */
        processTest: function() {
            this.processPreview();
        },
        /**
         * Process the form and execute the Search.
         */
        processExecute: function() {
            this.processPreview(true);
        },
        /**
         * Implementation for test/execute.
         * They use the exact same logic, so it's consolidated here.
         */
        processPreview: function(execute) {
            var data = this.readForm();
            this.App.showLoader();

            this.model[execute ? 'execute':'test'](data, {
                success: this.cbRendered(function(resp) {
                    this.App.setModal(new ResultsModalView(this.App, {
                        collection: new AlertCollection(resp, {}, resp.length)
                    }, resp.length));
                }),
                complete: $.proxy(this.App.hideLoader, this.App)
            });
        },
        processSave: function() {
            var data = this.readForm();

            var change_desc = this.$('textarea[name=change_description]');
            this.saveModel(data).success(this.cbRendered(function() {
                change_desc.val('');
            }));
            return false;
        },
        /**
         * Save filters and targets.
         */
        processSaveElements: function() {
            $.when(
                this.getView('filters').processSave(),
                this.getView('targets').processSave()
            ).then($.proxy(this.App.addMessage, this.App, 'Filters/Targets update successful', 2));
            return false;
        },
        /**
         * Load up a previous version of this Search.
         */
        loadLog: function(searchlog) {
            this.model.set(searchlog.toJSON().data);
            this.setPendingChanges();
            this.rerender();
        },
        /**
         * Show the statistics modal.
         */
        showStatistics: function() {
            this.App.setModal(new StatisticsModalView(this.App, {model: this.model}));
        },
        /**
         * Show the changelog modal.
         */
        showChangelog: function() {
            this.App.setModal(new ChangelogModalView(this.App, {model: this.model}));
        },
        /**
         * Show the jobs modal.
         */
        showJobs: function() {
            this.App.setModal(new JobsModalView(this.App, {model: this.model}));
        },
        /**
         * Show the delete modal.
         */
        showDelete: function() {
            var view = this.App.setModal(new ModelView.DeleteModalView(this.App, this.modelName));
            this.listenTo(view, 'button:delete', this.destroyModel);
        },
        /**
         * Delete this model and redirect to the searches page.
         */
        destroyModel: function() {
            ModelView.prototype.destroyModel.call(this, '/searches');
        }
    });

    // A mapping of type strings to classes. Used to determine which SearchView subclass
    // to load given just the type.
    var classMap = new ClassMap(SearchView);

    /**
     * A proxy SearchView that loads the search.
     * It determines the correct sub class of SearchView to load and replaces itself.
     * This solves the problem of not knowing which subclass to load for a given Search.
     */
    var SearchProxyView = ModelView.extend({
        modelClass: Search,

        _load: function(new_search, id) {
            // This is the entrypoint for both new_search and search.
            // loadCollectionsAndModel will generate a new model for us if
            // a valid id was not given. In the case that we want to clone
            // an existing search, we pull it down here.
            var link = new URI(window.location.href);
            var query = link.query(true);

            if('id' in query) {
                id = query.id;
            }
            this.loadCollectionsAndModel(
                [this.App.Data.Users, this.App.Data.Groups],
                this.App.Data.Searches, id,
                _.partial(this.loadSearchView, new_search)
            );
        },
        /**
         * Load the real SearchView.
         */
        loadSearchView: function(new_search) {
            // We have a model but it could be new. If so, we need to populate the type field.
            if(new_search) {
                if(!this.model.isNew()) {
                    this.model = this.model.clone();
                    this.model.destroy({soft: true});
                } else {
                    var link = new URI(window.location.href);
                    var query = link.query(true);

                    this.model.set('type', query.type);
                }
            }
            var type = this.model.get('type');

            // Pull out the subclass. Default to SearchView if not found.
            var newView = classMap.getSubclass(type);

            // Construct and load up the new view!
            var view = new newView(this.App, {model: this.model});
            this.App.loadView(view);
        }
    }, {
        // Expose SearchView so it can be accessed for subclassing.
        SearchView: SearchView,
        ElementsListView: ElementsListView,
        registerSubclass: $.proxy(ClassMap.prototype.registerSubclass, classMap)
    });

    return SearchProxyView;
});
