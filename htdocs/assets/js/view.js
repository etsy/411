"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
        Autosize = require('autosize');


    var states = {UNLOADED:0, LOADED:1, RENDERED:2};
    /**
     * The base View class.
     * Extends Backbone Views with the following features:
     * - View setup/shutdown via render/unrender and load/unload.
     *      Load/Unload should request/destroy any data that is necessary to render the View.
     *      Render/Unrender should create/destroy any DOM elements associated with the View.
     *          This includes event listeners and timers.
     *          Note that unrender will call stopListening() on the View.
     * - A BeforeUnload hook via onExit
     *     This allows the View to indicate whether it wants the user to stay on the page.
     * - Automatic setup/cleanup of child elements/Views registered under the View.
     *     Utility methods are provided to register elements and child Views.
     */
    var View = Backbone.View.extend({
        /**
         * View constructor
         * Requires that an application object be passed in.
         */
        constructor: function(app, options) {
            if(_.isUndefined(app)) {
                throw 'No application object!';
            }
            this.App = app;
            Backbone.View.call(this, options);

            // Current state of the View.
            this.state = states.UNLOADED;

            // Child elements/Views to manage.
            this.childElements = {};
            this.childViews = {};
            this.childViewNamesToIDs = {};
            this.childViewIDsToNames = {};
        },
        /**
         * Loaded status
         * @return {boolean} Whether the View is loaded
         */
        loaded: function() {
            return this.state >= View.State.LOADED;
        },
        /**
         * Rendered status
         * @return {boolean} Whether the View is rendered
         */
        rendered: function() {
            return this.state >= View.State.RENDERED;
        },
        /**
         * Load wrapper
         * Calls _load() which implements the actual loading logic.
         */
        load: function() {
            if(this.state >= View.State.LOADED) {
                throw 'Calling load() on a loaded view';
            }

            this.state = View.State.LOADED;
            this._load.apply(this, arguments);
            this.trigger('load');
        },
        /**
         * Load method
         * Responsible for fetching data and eventually calling render().
         */
        _load: function() {
            this.render();
        },
        /**
         * Unload wrapper
         * Calls _unload() which implements the actual unloading logic.
         */
        unload: function() {
            if(this.state > View.State.LOADED) {
                this.unrender();
            }

            if(this.state < View.State.UNLOADED) {
                throw 'Calling unload() on an unloaded view';
            }

            this.state = View.State.UNLOADED;
            this._unload.apply(this, arguments);
            this.trigger('unload');
        },
        /**
         * Responsible for unloading data.
         */
        _unload: function() {},
        /**
         * Reload the View.
         */
        reload: function() {
            this.unload();
            this.load();
        },

        /**
         * Render wrapper
         * Calls _render() which implements the actual rendering logic.
         */
        render: function() {
            if(this.state < View.State.LOADED) {
                throw 'Calling render() on a unloaded view';
            }
            if(this.state >= View.State.RENDERED) {
                throw 'Calling render() on a rendered view';
            }

            this.state = View.State.RENDERED;
            this._render.apply(this, arguments);
            this.delegateEvents();
            this.trigger('render');
            return this;
        },
        /**
         * Responsible for rendering the View.
         */
        _render: function() {},
        /**
         * Unrender wrapper
         * Calls _unrender() which implements the actual unrendering logic.
         */
        unrender: function() {
            if(this.state < View.State.RENDERED) {
                throw 'Calling unrender() on an unrendered view';
            }

            // Clean up listeners, destroy managed children and clear the container.
            this.stopListening();
            this.undelegateEvents();
            this.destroyElements();
            this.destroyViews();
            this.$el.text('');

            this.state = View.State.LOADED;
            this._unrender.apply(this, arguments);
            this.trigger('unrender');
        },
        /**
         * Responsible for unrendering the View.
         */
        _unrender: function() {},
        rerender: function() {
            this.unrender();
            this.render();
        },

        /**
         * Partially re-render the View.
         */
        update: function() {},

        /**
         * Apply slightly transparency to the view to indicate that it's processing
         */
        dim: function() {
            this.$el.css({'opacity': 0.3});
        },
        /**
         * Disable transparency effect.
         */
        undim: function() {
            this.$el.css({'opacity': 1.0});
        },

        /**
         * Destroy the View.
         * @param {boolean} remove - Whether to also call remove().
         */
        destroy: function(remove) {
            if(this.state >= View.State.RENDERED) {
                this.unrender();
            }
            if(this.state >= View.State.LOADED) {
                this.unload();
            }
            if(remove) {
                this.remove();
            }
            this.trigger('destroy', this);
        },

        /**
         * Load collections.
         * A convenience method to automatically load several collections and
         * then call render on the View.
         * This method is NOT synchronous.
         * @param {Array} collections - An array of Backbone collections to update.
         * @param {Function} func - A callback to execute once all Deferred are resolved.
         * @param {Array} deferred - An array of additional Deferred to resolve.
         * @return {Deferred} The Deferred object.
         */
        loadCollections: function(collections, func, deferred) {
            if(_.isUndefined(collections)) collections = [];
            if(_.isUndefined(func)) func = this.render;
            if(_.isUndefined(deferred)) deferred = [];

            for(var i = 0; i < collections.length; ++i) {
                deferred.push(collections[i].update());
            }
            return $.when.apply($, deferred).then(
                this.cbLoaded(func),
                $.proxy(this.App.hideLoader(), this)
            );
        },

        /**
         * Check if the View can exit. Executes recursively on child Views.
         * @return {boolean|string} true to allow, false to deny or a string to display a prompt.
         */
        onExit: function() {
            // Make sure all our children are ok with this.
            for(var k in this.childViews) {
                var ret = this.childViews[k].onExit();
                if(!_.isBoolean(ret) || !ret) {
                    return ret;
                }
            }
            return true;
        },

        /**
         * Return a previously registered selector.
         * @param {string} str - A jQuery selector string.
         */
        getElement: function(str) {
            return this.childElements[str];
        },
        /**
         * Register and return the selector.
         * @param {string} str - A jQuery selector string.
         */
        registerElement: function(str) {
            var selector = this.$(str);
            this.childElements[str] = selector;
            return selector;
        },
        /**
         * Cleanup an element and remove it from the list.
         * @param {string} k - The key for this element.
         */
        destroyElement: function(k) {
            var elems = this.childElements[k];
            if(_.isUndefined(elems)) {
                throw 'Called destroyChild on unknown key: ' + k;
            }

            // Loop over all elements in the selector.
            for(var i = 0; i < elems.length; ++i) {
                var elem = $(elems[i]);
                var data = elem.data();
                if(!data) {
                    continue;
                }

                // Do element specific cleanup if necessary.
                if('select2' in data) {
                    elem.select2('destroy');
                    delete data['select2'];
                } else if('autosize' in data) {
                    Autosize.destroy(elem);
                    delete data['autosize'];
                } else if('DateTimePicker' in data) {
                    data['DateTimePicker'].destroy();
                    delete data['DateTimePicker'];
                } else if('tablesorter' in data) {
                    elem.trigger('destroy');
                    delete data['tablesorter'];
                } else if('ScrollToFixed' in data) {
                    elem.trigger('detach.ScrollToFixed');
                    delete data['ScrollToFixed'];
                } else if('codemirror' in data) {
                    data['codemirror'].toTextArea();
                    delete data['codemirror'];
                }
                // Remove any listeners, just in case.
                elem.off();
            }
            delete this.childElements[k];
        },
        /**
         * Cleanup all registered elements.
         */
        destroyElements: function() {
            for(var k in this.childElements) {
                this.destroyElement(k);
            }
        },

        /**
         * Return a previously registered View.
         * @param {string|View} k - A View key.
         */
        getView: function(k) {
            var arr = k.substr(-2) === '[]';
            var str = this.childViewNamesToIDs[k];
            if(!_.isObject(str) && !arr) {
                return this.childViews[k] || this.childViews[str];
            }

            var views = [];
            for(var x in str) {
                var view = this.childViews[x];
                if(view) {
                    views.push(view);
                }
            }
            return views;
        },
        /**
         * Register a child View. Additionally attaches the View to the DOM if init is true.
         * @param {View} view - A View object.
         * @param {boolean} init - Whether to init and attach the View.
         * @param {Selector} sel - jQuery selector to use as a parent.
         * @param {string} str - An optional View key.
         */
        registerView: function(view, init, sel, str) {
            if(_.isUndefined(str)) str = view.cid;
            if(_.isUndefined(sel)) sel = this.$el;
            var arr = str.substr(-2) === '[]';
            if(this.childViewNamesToIDs[str] && !arr) {
                throw 'Adding View with duplicate key: ' + str;
            }
            if(this.childViews[view.cid]) {
                throw 'Adding existing view: ' + view.cid;
            }
            if(view.cid == this.cid) {
                throw 'Registering self';
            }
            if(!this.childViewNamesToIDs[str] && arr) {
                this.childViewNamesToIDs[str] = {};
            }

            this.listenTo(view, 'destroy', this.destroyView);
            this.childViews[view.cid] = view;
            if(arr) {
                this.childViewNamesToIDs[str][view.cid] = null;
            } else {
                this.childViewNamesToIDs[str] = view.cid;
            }
            this.childViewIDsToNames[view.cid] = str;

            if(init) {
                view.load();
                sel.append(view.el);
            }
            return view;
        },
        /**
         * Cleanup a View and remove it from the list.
         * @param {string|View} k - The key for the View or the View itself.
         */
        destroyView: function(k) {
            if(_.isObject(k)) k = k.cid;
            var view = this.childViews[k] || this.childViews[this.childViewNamesToIDs[k]];
            if(view) {
                // Stop listening to the View and call destroy on it.
                // Don't bother calling destroy if the view is UNLOADED
                this.stopListening(view);
                if(view.state > View.State.UNLOADED) {
                    view.destroy();
                }

                var name = this.childViewIDsToNames[view.cid];
                delete this.childViews[view.cid];
                delete this.childViewIDsToNames[view.cid];
                if(name.substr(-2) === '[]' && _.size(this.childViewNamesToIDs[name]) > 1) {
                    delete this.childViewNamesToIDs[name][view.cid];
                } else {
                    delete this.childViewNamesToIDs[name];
                }
            } else {
                throw 'Called destroyChild on unknown key: ' + k;
            }
        },
        /**
         * Cleanup child Views.
         */
        destroyViews: function() {
            // Call destroy on all children.
            for(var k in this.childViews) {
                this.destroyView(k);
            }
        },

        /**
         * Rendered callback wrapper
         * Executes a callback only if the View is in the rendered (or higher) state.
         */
        cbRendered: function(cb) {
            return $.proxy(function() {
                if(this.rendered()) { cb.apply(this, arguments); }
            }, this);
        },
        /**
         * Loaded callback wrapper
         * Executes a callback only if the View is in the loaded (or higher) state.
         */
        cbLoaded: function(cb) {
            return $.proxy(function() {
                if(this.loaded()) { cb.apply(this, arguments); }
            }, this);
        },

        getSelectableCount: function() {
            return 0;
        },
        onSelectionAction: function(i, j) {}
    }, {
        State: states
    });

    return View;
});
