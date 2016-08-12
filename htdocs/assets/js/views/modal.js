"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        View = require('view'),
        Templates = require('templates');


    /**
     * A modal View
     */
    var ModalView = View.extend({
        tagName: 'div',
        template: Templates['modal'],
        subTemplate: null,
        title: '',
        large: false,
        // Show the modal when rendered.
        showOnLoad: true,
        // Don't destroy the modal when it's hidden.
        persistent: false,
        // Definition for buttons.
        buttons: null,
        vars: null,
        events: null,

        initialize: function() {
            this.vars = this.vars || {};
            this.events = _.clone(this.events) || {};

            // Register events for each button. They should fire "button:NAME" events.
            // If the eventhandler already exists, replace it. Make sure to call the original.
            var events = {};
            var t = this;
            _(this.buttons).each(function(b) {
                var k = 'click .' + b.action + '-button';
                var func = t.events[k];
                // If func is a string, we need to grab a reference to the method.
                if(_.isString(func)) func = t[func];
                if(_.isUndefined(func)) func = _.constant(false);

                if(b.action) {
                    events[k] = b.persist ?
                        $.proxy(t.triggerF, t, 'button:' + b.action, func):
                        $.proxy(t.hideAndTrigger, t, 'button:' + b.action, func);
                }
            });
            _.extend(this.events, events);

            // Automatically destroy the modal on hide.
            this.events['hidden.bs.modal .modal'] = this.persistent ? 'unrender':'destroy';
        },
        _render: function() {
            this.$el.html(this.template({
                title: this.title,
                large: this.large,
                buttons: this.buttons,
            }));
            this.$('.modal-body').html(this.subTemplate(this.vars));

            if(this.showOnLoad) {
                this.show();
            }
        },
        /**
         * Trigger an event and return false.
         * @param {string} event - The name of the event to trigger.
         * @param {Function} func - An optional function to execute.
         * @param {boolean|undefined} - Return value for the event.
         * @param {*} params - Additional parameters to pass along.
         */
        triggerF: function(k, func, params) {
            this.trigger(k, params);
            return func.call(this, params);
        },
        /**
         * Show the modal
         * @param {Function} An optional callback to execute once the modal is shown.
         */
        show: function(callback) {
            // If the modal isn't rendered, do so. Ensure we don't accidentally call ourself.
            if(!this.rendered()) {
                var s = this.showOnLoad;
                this.showOnLoad = false;
                this.render();
                this.showOnLoad = s;
            }

            if(_.isUndefined(callback)) callback = _.noop;
            this.$('.modal').one('shown.bs.modal', callback).modal('show');
        },
        /**
         * Hide the modal
         * @param {Function} An optional callback to execute once the modal is hidden.
         */
        hide: function(callback) {
            if(_.isUndefined(callback)) callback = _.noop;
            this.$('.modal').one('hidden.bs.modal', callback).modal('hide');
        },
        /**
         * Modal visibility
         * @return {boolean} whether the modal is currently visible.
         */
        visible: function() {
            return !this.$('.modal').hasClass('hide');
        },
        /**
         * Hide the modal (destroy it) and trigger an event.
         * @param {string} event - The name of the event to trigger.
         * @param {*} params - Additional parameters to pass along.
         */
        hideAndTrigger: function(k, func, params) {
            this.$('.modal').one('hidden.bs.modal', $.proxy(this.triggerF, this, k, func, params)).modal('hide');
            return false;
        },
        destroy: function() {
            View.prototype.destroy.call(this, true);
        }
    });

    return ModalView;
});
