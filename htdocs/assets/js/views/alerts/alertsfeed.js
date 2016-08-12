"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        NavbarView = require('views/navbar'),
        AlertView = require('views/alerts/alert'),
        AlertGroupView = require('views/alerts/alertgroup'),
        CollectionView = require('views/collection'),
        Templates = require('templates'),
        Util = require('util'),
        Config = require('config'),

        AlertCollection = require('collections/alert'),
        AlertLogCollection = require('collections/alertlog');


    var AlertsFeedNavbarView = NavbarView.extend({
        title: 'Alerts Feed',
        notifications: false,
        links: [
            {name: 'Notifications', action: 'notif'},
            {name: 'Clear', action: 'clear'},
        ],
        events: {
            'click .notif-button': 'toggleNotifications',
            'click .clear-button': 'clear',
        },
        clear: function() {
            this.App.Bus.trigger('clear');
        },
        toggleNotifications: function(e) {
            var elem = $('.notif-button').parent();
            this.notifications = !this.notifications;
            elem.toggleClass('active', this.notifications);

            if(this.notifications) {
                Notification.requestPermission($.proxy(function(status) {
                    this.notifications = status === 'granted';

                    // Update the state of the button as appropriate.
                    elem.toggleClass('active', this.notifications);
                }, this));
            }
        }
    });

    var FeedCollectionView = CollectionView.extend({
        subView: AlertView.AlertLogView,
        initEntries: 15,
        maxEntries: 40,
        selectable: true,
        initialize: function() {
            CollectionView.prototype.initialize.call(this);

            this.App.Data.Alerts.reset();
        },
        update: function() {
            var query = Util.parseQuery(window.location.href);
            var lastTimestamp = this.collection.lastTimestamp;
            // If this is the first request, grab initEntries entries.
            if(lastTimestamp === 0) {
                query.reverse = 1;
                query.count = this.initEntries;
            }
            this.collection.update({
                data: query,
                success: this.cbRendered(function(collection, resp) {
                    // Remove entries if we're over quota.
                    while(collection.length > this.maxEntries) {
                        collection.models[0].destroy({soft: true});
                    }

                    // Grab the new alerts.
                    this.updateAlerts(lastTimestamp);
                })
            });
        },
        updateAlerts: function(lastTimestamp) {
            var ids = _.uniq(this.collection.pluck('alert_id'));
            if(ids.length > 0) {
                var query = {
                    id: ids
                };

                this.App.Data.Alerts.update({
                    data: query,
                    success: this.cbRendered(function(collection, resp) {
                        this.initializeCollection(lastTimestamp);
                    })
                });
            } else {
                this.initializeCollection(lastTimestamp);
            }
        },
        filterCollection: function(collection, lastTimestamp) {
            return collection.filter(function(model) {
                return model.get('create_date') > lastTimestamp;
            });
        },
        initializeCollectionData: function(lastTimestamp) {
            var frag = document.createDocumentFragment();

            // Construct and add the Views to the fragment.
            var t = this;
            var models = this.filterCollection(this.collection, lastTimestamp);
            for(var i = 0; i < models.length; ++i) {
                var view = t.initializeSubView(models[i]);
                frag.insertBefore(view.el, frag.firstChild);
            }
            return [models, frag];

        },
        initializeCollection: function(lastTimestamp) {
            var arr = this.initializeCollectionData(lastTimestamp);
            var models = arr[0],
                frag = arr[1];

            var counts = [0, 0];
            for(var i = 0; i < models.length; ++i) {
                counts[1 == models[i].get('action') ? 0:1] += 1;
            }
            if(models.length) {
                this.App.Bus.trigger('notif', counts);
            }
            this.$el.prepend(frag);
        },
        setSelectableDisplay: function(sel, selected, down) {
            $(sel.el).find('.panel')
                .toggleClass('panel-primary', selected)
                .toggleClass('panel-default', !selected);
        }
    });

    /**
     * Alerts Feed View.
     * Shows new alerts as they come in.
     */
    var AlertsFeedView = View.extend({
        template: _.constant(''),
        // A timer to periodically request new Alerts.
        intv: null,

        _load: function() {
            // Use a local collection, not the global App one.
            this.collection = new AlertLogCollection();
            this.collection.comparator = 'create_date';
            this.loadCollections([this.App.Data.Users, this.App.Data.Groups, this.App.Data.Searches]);
        },
        _unrender: function() {
            if(this.intv) {
                clearInterval(this.intv);
                this.intv = null;
            }
        },
        _render: function() {
            this.App.setTitle('Alerts Feed');
            var view = this.registerView(new AlertsFeedNavbarView(this.App), true, undefined, 'nav');
            this.listenTo(this.App.Bus, 'clear', this.clear);
            this.registerView(new FeedCollectionView(this.App, {collection: this.collection}), true, undefined, 'list');
            view.toggleNotifications();
            this.listenTo(this.App.Bus, 'notif', this.createNotification);

            this.intv = setInterval($.proxy(this.update, this), 4000);
            this.App.hideLoader();
        },
        clear: function() {
            this.getView('list').clear();
            this.update();
        },
        update: function() {
            this.getView('list').update();
        },
        createNotification: function(counts) {
            if(!this.getView('nav').notifications || document.hasFocus()) {
                return;
            }
            var notif = new Notification((counts[0] + counts[1]) + ' new events', {
                body: counts[0] + ' Alerts, ' + counts[1] + ' actions',
                icon: Config.asset_root + '/imgs/notif.png',
            });
            setTimeout(function() {
                notif.close();
            }, 5000);
        }
    });

    return AlertsFeedView;
});
