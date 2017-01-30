"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
        Mousetrap = require('mousetrap'),
        URI = require('uri'),
        Util = require('util'),
        Templates = require('templates'),

        Router = require('router'),
        Config = require('config'),
        Data = require('data'),

        Model = require('model'),
        Collection = require('collection'),
        SearchCollection = require('collections/search'),
        AlertCollection = require('collections/alert'),
        UserCollection = require('collections/user'),
        GroupCollection = require('collections/group'),
        ReportCollection = require('collections/report'),
        ListCollection = require('collections/list'),

        HeaderView = require('views/header'),
        FooterView = require('views/footer'),
        HelpModalView = require('views/help'),
        MessagesModalView = require('views/messages');

    require('routefilter');
    require('bootstrap');
    require('tablesorter');
    require('select2');
    require('datetimepicker');
    require('helper');
    require('autosize');
    require('views/searches/search/load');
    require('views/filter/load');
    require('views/target/load');
    require('views/renderer/load');

    /**
     * Simple message bus (borrowed from backbone.radio)
     * Used with the current view to allow sub Views to send messages in a consistent manner.
     */
    var Bus = function() {};
    _.extend(Bus.prototype, Backbone.Events, {
        /**
         * Remove all listeners.
         */
        reset: function() {
            this.off();
            this.stopListening();
        }
    });

    /**
     * Message object/collection
     */
    var Message = Model.extend({});
    var MessageCollection = Collection.extend({
        model: Message,
        comparator: function(a, b) { return b.get('date') - a.get('date'); }
    });

    /**
     * The App class
     * Contains global references and state.
     */
    var App = function() {
        // Store a global collection of each type. This allows for caching objects between pages.
        this.Data = {
            Searches: new SearchCollection(),
            Alerts: new AlertCollection(),
            Users: null,
            Groups: new GroupCollection(),
            Reports: new ReportCollection(),
            Lists: new ListCollection(),
            Nonce: Data.Nonce,
            User: null,
        };

        // Create the router.
        this.Router = new Router(this);

        // Store references to root elements.
        this.Elem = {
            Header: $('#header'),
            Message: $('#message'),
            Loader: $('#loader'),
            Container: $('#container'),
            Footer: $('#footer'),
            Modal: $('#modal')
        };

        // Store references to the active views.
        this.View = {
            Header: new HeaderView(this, {el: this.Elem.Header}),
            Footer: new FooterView(this, {el: this.Elem.Footer}),
            Modal: null,
            Current: null
        };

        // Message bus.
        this.Bus = new Bus();

        // Store last route for the routing logic.
        this.lastRoute = null;

        // Store help information for key sequences.
        this.keysHelp = [{}, {}];

        // A list of historical messages.
        this.messages = new MessageCollection();

        // Selectable group management.
        this.selectableGroups = [];
        this.groupIndex = 0;
        this.itemIndex = -1;

        // Bind the ajax function to this object.
        this.ajax = $.proxy(this._ajax, this);

        // Login.
        this.refresh();
    };

    _.extend(App.prototype, {
        /**
         * Refresh with a new Data object.
         */
        refresh: function() {
            this.Data.Users = new UserCollection(Data.User.Models);
            this.Data.User = this.Data.Users.get(Data.User.Me);
            this.View.Header.unload();
            this.View.Footer.unload();
            this.View.Header.load();
            this.View.Footer.load();

        },

        /**
         * Set the title of the page.
         * @param {string} loc - The title to show.
         * @param {string} pre - A prefix. Defaults to 'APP_NAME / '.
         */
        setTitle: function(loc, pre) {
            if(_.isUndefined(pre)) pre = Data.AppName + ' / ';
            document.title = pre + loc;
        },
        /**
         * Add an alert to the top of the page.
         * @param {string} str - The message to display.
         * @param {int} lvl - The message level.
         */
        addMessage: function(str, lvl) {
            this.messages.add(new Message({
                level: lvl,
                date: Date.now() / 1000 | 0,
                message: str
            }));

            var level = Util.getLevel(lvl);
            var message_elem = $(Templates['message']({
                level: level, message: str.replace(/\n/g, '<br>')
            }));
            this.Elem.Message.append(message_elem);
            message_elem.delay(3000).slideUp(message_elem.remove);
        },
        /**
         * Clear all messages on the page.
         */
        clearMessages: function() {
            this.messages.reset();
        },
        /**
         * Show the help modal.
         */
        showHelp: function() {
            var help = new HelpModalView(this);
            this.setModal(help);
        },
        /**
         * Show the message history modal.
         */
        showMessages: function() {
            var messages = new MessagesModalView(this);
            this.setModal(messages);
        },
        /**
         * Set the current modal.
         * @return {ModalView} The modal for chaining.
         */
        setModal: function(modal) {
            if(this.View.Modal && this.View.Modal.visible()) {
                this.View.Modal.hide();
            }
            this.View.Modal = modal;
            this.listenTo(modal, 'destroy', this.clearModal);
            this.Elem.Modal
                .append(modal.el);
            modal.load();
            return modal;
        },
        /**
         * Unset the current modal.
         */
        clearModal: function() {
            if(!this.View.Modal) {
                return;
            }
            this.View.Modal.hide();
            this.stopListening(this.View.Modal);
            this.View.Modal = null;
        },
        /**
         * Register a new keyboard shortcut.
         * @param {string} keys - Keyboard shortcut string.
         * @param {Function} callback - A callback function to execute.
         * @param {string} help - A help string.
         * @param {boolean} global - Whether this shortcut persists across pages.
         */
        registerKbdShortcut: function(keys, callback, help, global) {
            if(keys in this.keysHelp) {
                throw 'Registering existing key sequence: ' + keys;
            }

            this.keysHelp[!global|0][keys] = help;
            Mousetrap.bind(keys, $.proxy(function() {
                if(this.View.Modal && this.View.Modal.visible()) {
                    return;
                }
                callback();
            }, this));
        },
        /**
         * Register a new keyboard shortcut that acts on a selectable.
         * @param {string} keys - Keyboard shortcut string.
         * @param {string} action - The action to execute on the selectable.
         * @param {string} help - A help string.
         * @param {boolean} global - Whether this shortcut persists across pages.
         */
        registerSelectableKbdShortcut: function(keys, action, help, global) {
            this.registerKbdShortcut(keys, $.proxy(this.selectSelectable, this, action), help, global);
        },
        /**
         * Remove a previously registered keyboard shortcut.
         * @param {string} keys - The shortcut to remove.
         */
        destroyKbdShortcut: function(keys) {
            for(var i = 0; i < this.keysHelp.length; ++i) {
                delete this.keysHelp[i][keys];
            }
            Mousetrap.unbind(keys);
        },
        /**
         * Remove all* previously registered keyboard shortcuts.
         * @param {boolean} global - Whether to remove global shortcuts too.
         */
        destroyKbdShortcuts: function(global) {
            var targets = [1];
            if(global) {
                targets.push(0);
            }
            for(var i = 0; i < targets.length; ++i)  {
                for(var k in this.keysHelp[targets[i]]) {
                    this.destroyKbdShortcut(k);
                }
            }
        },
        registerSelectableGroup: function(view) {
            this.selectableGroups.push(view);
            this.listenTo(view, 'destroy', this.removeSelectableGroup);
        },
        removeSelectableGroup: function(obj) {
            for(var i = 0; i < this.selectableGroups.length; ++i) {
                if(this.selectableGroups[i] == obj) {
                    this.selectableGroups.splice(i, 1);
                }
            }
            this.groupIndex = 0;
            this.itemIndex = -1;
        },
        /**
         * Show the loading overlay.
         */
        showLoader: function() {
            this.Elem.Loader.stop().fadeIn(200);
        },
        /**
         * Hide the loading overlay.
         */
        hideLoader: function() {
            this.Elem.Loader.stop().fadeOut(200);
        },
        /**
         * Extended jQuery ajax function.
         * Automatically logs when an exception occurs.
         * Also automatically adds the Nonce to POST requests.
         * @param {Object} options - Parameters to pass to jquery's ajax function.
         */
        _ajax: function(options) {
            options = options || {};

            // Whether we've displayed the results of this request.
            var processed = false;
            var _callback = $.proxy(function(callback, resp, status, err) {
                var message = null;
                var message_type = 2;

                var data = resp.responseJSON || resp;

                // If this is a standard response object...
                if('success' in data && _.isBoolean(data['success'])) {
                    message = data.message;
                    message_type = data.success ? 2:0;

                    // If we get an auth error, we've lost the session. Punt back to the login page.
                    if(!data.success && !data.authenticated) {
                        this.refresh();
                        this.Router.navigate('/login', {trigger: true});
                        return;
                    }
                } else {
                // Otherwise, we've got a problem.
                    message = 'Unexpected error: ' + status;
                    message_type = 0;
                }

                if(message && !processed) {
                    if(!_.isArray(message)) {
                        message = [message];
                    }
                    for(var i = 0; i < message.length; ++i) {
                        this.addMessage(message[i], message_type);
                    }
                    processed = true;
                }

                if(callback) callback('data' in data ? data.data:resp, status, err);
            }, this);

            options.success = _.partial(_callback, options.success);
            options.complete = _.partial(_callback, options.complete);
            options.error = _.partial(_callback, options.error);

            // Add the nonce if this is not a GET request.
            if(('type' in options && options.type.toLowerCase() != 'get') ||
               ('method' in options && options.method.toLowerCase() != 'get')
            ) {
                options.headers = options.headers || {};
                options.headers['X-Nonce'] = this.Data.Nonce;
            }

            return $.ajax(options);
        },
        /**
         * Error handler.
         */
        error: function(e) {
            this.addMessage('Error: ' + e);
        },
        /**
         * Click handler.
         * Determines how clicks on anchor elements should be processed.
         * If the path is different, navigates to that endpoint.
         * Otherwise, call update on the current View.
         */
        click: function(e) {
            var anchor = e.currentTarget;

            var alink = new URI(anchor.href);
            var clink = new URI(window.location.href);

            var apath = alink.path();
            var cpath = clink.path();
            var dest = alink.resource();


            // If the link doesn't point anywhere, contains a fragment,
            // opens a new page or points to another host, or if a modifier
            // key was held, allow it through.
            if(
                !('href' in anchor) ||
                anchor.target === '_blank' ||
                anchor.href.length === 0 ||
                anchor.href.indexOf('#') !== -1 ||
                alink.host() != clink.host() ||
                e.metaKey || e.ctrlKey || e.altKey
            ) {
                return;
            }

            // Check if the path is the same.
            var trigger = apath !== cpath;
            // Either way, the url needs to be updated.
            this.Router.navigate(dest, {trigger: trigger});
            // But if they were the same, we call the update method on the View.
            if(!trigger) {
                this.View.Current.update();
            }
            e.preventDefault();
        },
        /**
         * Selectable change handler.
         */
        changeSelectable: function(down, group) {
            // Don't do anything if we have no selectables.
            if(this.selectableGroups.length === 0) {
                return;
            }

            // Determine next value.
            var delta = down ? 1:-1;
            var i = this.groupIndex;
            var j = this.itemIndex + delta;
            var group;
            var group_size;
            var group_changes = 0;

            var update = $.proxy(function(i) {
                group = i >= 0 && i < this.selectableGroups.length ? this.selectableGroups[i]:null;
                group_size = group ? group.getSelectableCount():0;
            }, this);
            update(i, j);

            while((i !== this.groupIndex || j !== this.itemIndex) && group_changes < this.selectableGroups.length) {
                if(j >= group_size || j < 0) {
                    i = (i + this.selectableGroups.length + delta) % this.selectableGroups.length;
                    update(i);
                    j = down ? 0:(group_size - 1);
                    ++group_changes;
                }
                if(group !== null && j >= 0 && j < group_size) {
                    break;
                }

                j += delta;
            }

            if(this.itemIndex !== -1) {
                var old_group = this.selectableGroups[this.groupIndex];
                if(old_group.getSelectableCount() > this.itemIndex) {
                    old_group.setSelectable(this.itemIndex, false, down);
                }
            }
            var group = this.selectableGroups[i];
            group.setSelectable(j, true, down);

            this.groupIndex = i;
            this.itemIndex = j;
        },
        selectSelectable: function(action) {
            if(this.itemIndex !== -1) {
                this.selectableGroups[this.groupIndex].selectSelectable(action, this.itemIndex);
            }
        },
        /**
         * Callback to determine if current View doesn't want the user to navigate away.
         */
        beforeUnload: function() {
            var val = this.View.Current ? this.View.Current.onExit():null;
            return _.isBoolean(val) && val ? undefined:val;
        },
        /**
         * Callback to determine whether to allow routing to a View.
         * Checks if the current View doesn't want the user to navigate away.
         */
        beforeRoute: function(route) {
            if(this.View.Current) {
                var val = this.View.Current.onExit();
                // onExit returned false, deny.
                var deny = _.isBoolean(val) && !val;
                // onExit returned string, deny if the user is ok with it.
                if(_.isString(val)) {
                    deny = !confirm(val);
                }

                // User decided to stay on current View.
                if(deny) {
                    this.Router.navigate(this.lastRoute);
                    return false;
                }
            }

            // Clean up the display.
            this.showLoader();

            var link = new URI(window.location.href);
            this.lastRoute = link.resource();

            // Redirect to the forbidden page if trying to hit an admin page (and not admin).
            if((!this.Data.User || !this.Data.User.get('admin')) && _.contains(['admin'], route)) {
                this.Router.navigate('forbidden', {trigger: true});
                return false;
            }

            // Redirect to the login page if trying to hit an authenticated page.
            if(!this.Data.User && !_.contains(['', 'login', 'logout', 'forbidden'], route)) {
                this.Router.navigate('login?redirect=' + link.resource(), {trigger: true, replace: true});
                return false;
            }

            // Redirect to page if already authenticated.
            if(this.Data.User && route == 'login') {
                this.Router.navigate(link.query(true)['redirect'], {trigger: true, replace: true});
                return false;
            }

            // Otherwise, let this route execute.
        },
        /**
         * Load a view and set it as the current view. The previous view is automatically destroyed.
         * @param {View} view - The new view to render.
         * @param {Object} params - Parameters to pass to the View's load function.
         */
        loadView: function(view, params) {
            this.unloadView();

            // Setup the current view and call load. Fade in the new view.
            var el = $(this.Elem.Container);
            el.hide();
            this.View.Current = view;
            view.setElement(this.Elem.Container);
            el.fadeIn();
            view.load.apply(view, params);
        },
        unloadView: function() {
            // Unload the current view, if it exists.
            if(this.View.Current) {
                this.destroyKbdShortcuts();
                this.View.Current.destroy();
                this.View.Current = null;
            }

            // Remove any modals.
            this.clearModal();

            // Clear the message bus.
            this.Bus.reset();

            // Clear the page.
            this.Elem.Container.text('');
        },
        /**
         * Register callbacks and start the application.
         */
        start: function() {
            // Register our ajax function in Backbone.
            Backbone.ajax = this.ajax;

            // Register our error handler.
            window.onerror = $.proxy(this.error, this);

            // Attach & render the help modal.
            this.registerKbdShortcut('?', $.proxy(this.showHelp, this), 'Open this dialog', true);

            // Attach the selectable group handlers.
            this.registerKbdShortcut('k', $.proxy(this.changeSelectable, this, false), 'Select previous item', true);
            this.registerKbdShortcut('j', $.proxy(this.changeSelectable, this, true), 'Select next item', true);
            this.registerKbdShortcut('o', $.proxy(this.selectSelectable, this, 'open'), 'Open item', true);

            // Register routing callbacks.
            $(document.body).on('click', 'a', $.proxy(this.click, this));
            window.onbeforeunload = $.proxy(this.beforeUnload, this);
            window.onunload = $.proxy(this.unloadView, this);

            // Start routing!
            Backbone.history.start({pushState: true, hashChange: false, root: Config.doc_root});

            console.log('Load complete');
        }
    }, Backbone.Events);

    return App;
});
