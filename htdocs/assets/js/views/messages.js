"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        ModalView = require('views/modal'),
        TableView = require('views/table'),
        Handlebars = require('handlebars'),
        Collection = require('collection'),
        Util = require('util');


    var MessageEntryView = View.extend({
        tagName: 'tr',
        template: Handlebars.compile('<td>{{date time}}</td><td class="{{ type }}">{{ str }}</td>'),
        _render: function() {
            var message = this.model.get('message');
            if(_.isArray(message)) {
                message = message.join('<br>');
            }

            this.$el.html(this.template({
                type: Util.getLevel(this.model.get('level')),
                time: this.model.get('date'),
                str: message,
            }));
        }
    });

    var MessagesTableView = TableView.extend({
        subView: MessageEntryView,
        columns: [
            {name: 'Date', sorter: 'false'},
            {name: 'Message', sorter: 'false', width: 70},
        ],
        update: TableView.prototype.initializeCollection,
    });

    var MessagesModalView = ModalView.extend({
        subTemplate: _.constant(''),
        title: 'Messages',
        large: true,
        buttons: [
            {name: 'Clear', type: 'danger', icon: 'trash', action: 'clear'}
        ],
        events: {
            'click .clear-button': 'clearMessages'
        },
        _render: function() {
            ModalView.prototype._render.call(this);
            var view  = new MessagesTableView(this.App, {collection: this.App.messages});
            this.registerView(view, true, this.$('.modal-body'));
        },
        clearMessages: function() {
            this.App.clearMessages();
        }
    });

    return MessagesModalView;
});
