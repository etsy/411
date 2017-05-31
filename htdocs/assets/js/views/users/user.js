"use strict";
define(function(require) {
    var _ = require('underscore'),
        NavbarView = require('views/navbar'),
        ModelView = require('views/model'),
        Moment = require('moment'),
        Templates = require('templates'),
        Util = require('util'),
        User = require('models/user');


    var UserNavbarView = NavbarView.extend({
        title: 'User',
    });

    /**
     * The user View
     */
    var UserView = ModelView.extend({
        modelName: 'User',
        modelClass: User,
        modelUrl: '/user/',

        events: {
            'click #create-button': 'processSave',
            'click #update-button': 'processSave',
            'click #delete-button': 'showDelete',
        },
        template: Templates['users/user'],
        _load: function(id) {
            this.loadCollectionsAndModel([], this.App.Data.Users, id);
        },
        _render: function() {
            this.App.setTitle('User: ' + (this.model.isNew() ? 'New':this.model.get('id')));
            this.registerView(new UserNavbarView(this.App), true);

            var user_tz = 'UTC';
            if ('timezone' in this.model.get('settings')) {
                user_tz = this.model.get('settings').timezone;
            }

            // prep tz data for display
            var timezones  = _.map(Moment.tz.names(), function(tz){
                return {timezone: tz, selected: (tz === user_tz)};
            }, this);
            timezones.unshift({timezone: 'LocalBrowserTime', selected: ('LocalBrowserTime' === user_tz) });

            var vars = this.model.toJSON();
            _.extend(vars, {
                new_user: this.model.isNew(),
                timezones: timezones
            });

            this.$el.append(this.template(vars));

            this.registerElement('.generate-key').click($.proxy(this.generateKey, this));
            Util.initSelectAll(this.registerElement('.select-all'));

            if(this.model.isNew()) {
                this.generateKey();
            }

            this.detectChanges();

            this.App.hideLoader();
        },
        generateKey: function() {
            if(!this.isSelf() && !this.App.Data.User.get('admin')) {
                return;
            }
            var arr = new Uint8Array(24);
            window.crypto.getRandomValues(arr);
            this.$('input[name=api_key]').val(btoa(String.fromCharCode.apply(null, arr)));
            this.pendingChanges = true;
        },
        isSelf: function() {
            return this.model.get('id') == this.App.Data.User.get('id');
        },
        readForm: function() {
            var form = this.$('#user-form');
            var data = Util.serializeForm(form);

            data.settings = {};
            data.settings.timezone = data.timezone;
            delete data.timezone;

            data.admin = !!parseInt(data.admin, 10);

            return data;
        },
        /**
         * Process the form and commit model changes to the server.
         */
        processSave: function() {
            var data = this.readForm();

            // Don't allow removing admin from yourself.
            if(this.isSelf() && !data.admin && this.model.get('admin')) {
                this.App.addMessage('Unable to deprivilege this user');
                return false;
            }

            // Verify that the passwords match!
            if(data.password !== data.password_) {
                this.App.addMessage('Passwords do not match!');
                return false;
            }
            // We don't want to ship the dupe password, so delete it.
            delete data.password_;

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
         * Delete this model and redirect to another location.
         * Extended to prevent users from deleting their own account.
         * @param {string} url - The url to navigate to on success.
         */
        destroyModel: function() {
            if(this.model.get('id') == this.App.Data.User.get('id')) {
                this.App.addMessage('Unable to delete this user');
            } else {
                ModelView.prototype.destroyModel.call(this, '/users');
            }
        }
    });

    return UserView;
});
