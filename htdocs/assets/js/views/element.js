"use strict";
define(function(require) {
    var _ = require('underscore'),
        ModelView = require('views/model'),
        Templates = require('templates'),
        Util = require('util');


    var ElementView = ModelView.extend({
        tagName: 'div',
        template: Templates['element'],
        compactTemplate: Templates['elementcompact'],

        // Whether to hide the buttons for reordering multiple elements.
        hide_config: true,
        // Whether to hide the up/down/close ui.
        hide_chrome: false,
        // Whether to hide the lifetime/description inputs.
        temp: false,
        // Whether to show the compact view
        compact: false,

        initialize: function(options) {
            ModelView.prototype.initialize.call(this);

            if('compact' in options) {
                this.compact = options.compact;
            }

            this.vars = _.clone(this.vars) || {};
            this.events = _.clone(this.events) || {};

            this.events['click .close-button'] = 'delete';
            this.events['click .add-element'] = 'addElement';
            this.events['click .delete-element'] = 'deleteElement';
            this.events['click .up-button'] = 'up';
            this.events['click .down-button'] = 'down';
            this.events['click .edit-button'] = 'edit';
        },
        __render: function() {},
        _render: function() {
            var vars = _.extend({
                name: this.modelClass.Data().Types[this.model.get('type')],
                desc: this.modelClass.Data().Descriptions[this.model.get('type')],
                temp: this.temp,
                hide_config: this.hide_config,
                hide_chrome: this.hide_chrome
            }, this.vars, this.model.toJSON());
            vars.data = Util.formatFields(this.model.get('data'), this.modelClass.Data().Data[this.model.get('type')]);
            var tpl = this.compact ? this.compactTemplate:this.template;
            this.$el.append(tpl(vars));

            this.__render();

            if(!this.compact) {
                Util.initTimeSelect(this.registerElement('input[name=lifetime]'), {
                    allow_zero: true,
                    format: function(num) { return parseInt(num, 10) === 0 ? 'Forever':Util.formatTime(num); }
                });
            }

            // If the model changes, notify that we have unsaved changes.
            this.listenTo(this.model, 'change', this.change);
            Util.autosize(this.registerElement('textarea'));
        },
        change: function() {
            this.setPendingChanges();
            this.rerender();
        },
        readForm: function() {
            var data = Util.serializeForm(this.$el);
            var ret = {
                data: data,
                lifetime: data.lifetime,
                description: data.description
            };
            delete data.description;
            delete data.lifetime;
            return ret;
        },
        up: function() {
            this.trigger('up', this);
        },
        down: function() {
            this.trigger('down', this);
        },
        delete: function() {
            this.trigger('delete', this);
        },
        edit: function() {
            this.trigger('edit', this);
        },
        /**
         * Insert a new entry.
         */
        addElement: function(e) {
            var old_elem = $(e.currentTarget).closest('.input-group');
            // Only continue if something was entered.
            var old_inp = old_elem.find('input');
            if(!old_inp.val().length) {
                return;
            }
            var new_elem = old_elem.clone();
            new_elem.find('button').removeClass('add-element').addClass('delete-element');
            new_elem.find('.glyphicon').removeClass('glyphicon-plus').addClass('glyphicon-minus');
            old_inp.val('');
            old_elem.before(new_elem);
        },
        /**
         * Delete the entry.
         */
        deleteElement: function(e) {
            $(e.currentTarget).closest('.input-group').remove();
        },
    });

    return ElementView;
});
