"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        ClassMap = require('classmap'),
        Config = require('config');


    /**
     * Renderer class
     * Takes text data and extracts useful info from it.
     */
    var Renderer = {};
    Renderer.extend = function(source) {
        return _.extend({}, Renderer, source);
    };

    var classMap = new ClassMap(Renderer);
    Renderer.renderers = classMap.getSubclasses();
    Renderer.registerSubclass = $.proxy(ClassMap.prototype.registerSubclass, classMap);

    /**
     * Whether this renderer should be automatically invoked.
     */
    Renderer.auto = false;

    /**
     * Report how interested this renderer is in the data.
     * Setting this value to true/false means the renderer should always/never run.
     *
     * @param key - The key.
     * @param val - The value.
     * @return A number between 0 and 1. Any number >= 0.75 will execute the renderer.
     */
    Renderer.match = false;

    /**
     * The name of the remote Enricher to request data from.
     */
    Renderer.remote = null;

    /**
     * Render a preview of the data. Previews must not require remote access.
     */
    Renderer.preview = function(key, val) {};

    /**
     * Render the data.
     */
    Renderer.render = function(key, val, data) {};

    /**
     * Serialize a mapping so it can be used for subsequent hits.
     */
    Renderer.serialize = function(in_mapping) {
        var out_mapping = {};
        for(var i = 0; i < in_mapping.length; ++i) {
            var key = in_mapping[i].key;
            out_mapping[key] = in_mapping[i].getRenderers();
        }
        return out_mapping;
    };

    Renderer.deserialize = function(App, mapping, model_id, in_mapping, callback, preview) {
        var inter_mapping = {};
        var view_mapping = {};

        // Make a mapping of keys to views.
        for (var i = 0; i< in_mapping.length; ++i) {
            inter_mapping[in_mapping[i].key] = in_mapping[i];
        }

        // Now use this mapping to define a struct mapping keys to renderers.
        for (var k in mapping) {
            if (k in inter_mapping) {
                view_mapping[k] = [inter_mapping[k], mapping[k]];
            }
        }

        var out_mapping = {};
        out_mapping[model_id] = view_mapping;
        return Renderer.process(App, out_mapping, callback, preview);
    };
    /**
     * Automatically process a group of FieldViews.
     */
    Renderer.autoProcess = function(App, in_mapping, callback) {
        // Rank all renderers to determine whether they should run.
        var out_mapping = {};

        // First loop over each group of views.
        for(var v in in_mapping) {
            var views = in_mapping[v];
            var view_mapping = {};
            // Then over the actual views.
            for(var i = 0; i < views.length; ++i) {
                var renderer_list = [];
                // Then over all the renderers.
                for(var c in Renderer.renderers) {
                    var renderer = Renderer.renderers[c];
                    // Only process auto renderers.
                    if(!renderer.auto) {
                        continue;
                    }

                    var rank = _.isBoolean(renderer.match) ?
                        (renderer.match|0):renderer.match(views[i].key, views[i].value);
                    if(rank >= 0.75) {
                        renderer_list.push(c);
                    }
                }
                // Only add the views to the list if there are views to process.
                if(renderer_list.length) {
                    view_mapping[views[i].key] = [views[i], renderer_list];
                }
            }
            // Only add the views to the list if there are view groups to process.
            if(!_.isEmpty(view_mapping)) {
                out_mapping[v] = view_mapping;
            }
        }

        return Renderer.process(App, out_mapping, callback, false);
    };

    var processResponse = function(App, base_mapping, resp, callback) {
        // First loop over each group of views.
        for(var v in resp) {
            // Not a group we sent, abort.
            if(!(v in base_mapping)) {
                continue;
            }
            var view_data = resp[v];
            var view_mapping = base_mapping[v];
            // Then over the actual views.
            for(var k in view_data) {
                // Not a key we sent, abort.
                if(!(k in view_mapping)) {
                    continue;
                }
                var view = view_mapping[k][0],
                    renderers = view_mapping[k][2],
                    val = view_mapping[k][3],
                    data = view_data[k];

                var renderer = Renderer.renderers[renderers.shift()];
                var rendered_val = renderer.render(view.key, val, data);
                view_mapping[k][3] = _.isUndefined(rendered_val) ? val:rendered_val;
            }
        }

        // "Recurse" into process again.
        Renderer.process(App, base_mapping, callback, false);
    };

    /**
     * Render some data.
     * Automatically batches up and makes calls to the backend for Enrichers to do their work.
     */
    Renderer.process = function(App, base_mapping, callback, preview) {
        var remote_mapping = {};

        // First loop over each group of views.
        for(var v in base_mapping) {
            var view_mapping = base_mapping[v];
            // Then over the actual views.
            for(var k in view_mapping) {
                var view = view_mapping[k][0],
                    orig_renderers = view_mapping[k][1];
                var renderers = view_mapping[k][2] = view_mapping[k][2] || orig_renderers.slice(),
                    val = view_mapping[k][3] || view.value;

                // Process renderers until we hit a remote or we're done.
                while(renderers.length) {
                    var renderer = Renderer.renderers[renderers[0]];
                    if(!_.isNull(renderer.remote) && !preview) {
                        break;
                    }
                    val = preview ?
                        renderer.preview(view.key, val):renderer.render(view.key, val);
                    renderers.shift();
                }
                // Consume one remote.
                if(renderers.length) {
                    var renderer = Renderer.renderers[renderers[0]];
                    var node = remote_mapping[v] = {};
                    node[k] = [[renderer.remote], val];
                }
                // If we're done with this view, delete it.
                if(renderers.length === 0) {
                    view.update(orig_renderers, val, preview);
                    delete view_mapping[k];
                // Otherwise, save our intermediate value for further processing.
                } else {
                    view_mapping[k][3] = val;
                }
            }
            // If we're done with this group, delete it.
            if(_.isEmpty(view_mapping)) {
                delete base_mapping[k];
            }
        }

        // If there are no remotes, we're done!
        if(_.isEmpty(remote_mapping)) {
            if(callback) callback();
            return;
        }

        // Process one layer of server requests.
        App.ajax({
            url: Config.api_root + 'enrich',
            method: 'post',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify(remote_mapping),
            success: function(resp) {
                _.defer(processResponse, App, base_mapping, resp, callback);
            }
        });
    };

    return Renderer;
});
