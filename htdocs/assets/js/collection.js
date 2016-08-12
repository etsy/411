"use strict";
define(function(require) {
    var _ = require('underscore'),
        Backbone = require('backbone');


    // Retrieve a field from the model, accounting for objects.
    var getField = function(m, k) {
        return m instanceof Backbone.Model ?
            m.get(k):m[k];
    };
    // Some custom update logic.
    var update = function(models, options) {
        if(!_.isArray(models) && models) {
            models = [models];
        }

        if(!models || models.length === 0) {
            return;
        }

        // Update the lastTimestamp field.
        for(var i in models) {
            var create = getField(models[i], 'create_date');
            var update = getField(models[i], 'update_date');
            if(create > this.lastTimestamp) {
                this.lastTimestamp = create;
            }
            if(update > this.lastTimestamp) {
                this.lastTimestamp = update;
            }
        }

        // Automatically destroy any models that have been archived.
        _.each(this.where({archived: true}), function(model) {
            model.destroy({soft: true});
        });
    };

    // Base collection class. Extended with some useful partial-update functions
    var Collection = Backbone.Collection.extend({
        // The most recent timestamp within the models. All models are expected to have create_date and update_date fields.
        lastTimestamp: 0,
        query: null,

        // Fetch models that have changed since lastTimestamp.
        update: function(options) {
            options = options ? _.clone(options) : {};
            // Don't remove existing models.
            options.remove = false;
            options.data = options.data || {};
            // Add the timestamp so the server knows what models to send.
            options.data.time = this.lastTimestamp;
            // If an id was passed, only retrieve that model.
            var old_timestamp = this.lastTimestamp;
            if(!_.isNull(this.query)) {
                _.extend(options.data, this.query);
            }

            var success = options.success;
            options.success = function(collection, resp, options) {
                // If a specific id was fetched, don't want to update lastTimestamp. However, set will already have done so. Set lastTimestamp back to the previous value.
                if('id' in options.data) {
                    collection.lastTimestamp = old_timestamp;
                }

                // Execute the callback, if it exists.
                if(success) success(collection, resp, options);
            };

            return this.fetch(options);
        },
        // Accepts a list of changed attributes for each model and pushes them to the server.
        save: function(data, options) {
            if(_.isUndefined(data)) {
                data = [];
                for(var k in this.models) {
                    var attrs = this.models[k].toJSON();
                    if(_.isUndefined(this.models[k]['id'])) {
                        attrs.cid = this.models[k].cid;
                    }
                    data.push(attrs);
                }
            }

            var collection = this;

            options = options || {};
            var success = options.success;
            options.success = function(response, status, xhr) {
                // Update the collection with the response, but don't delete anything!
                collection.set(response, {remove: false});

                collection.trigger('sync');
                if(success) success(response, status, xhr);
            };

            var url = _.isFunction(this.url) ? this.url():this.url;
            var model = {
                url: url,
                toJSON: function() {
                    return {models: data};
                },
                trigger: function() {}
            };

            return this.sync('update', model, options);
        },
        // Reset, extended to perform some extra bookkeeping.
        reset: function(models, options) {
            this.lastTimestamp = 0;
            Backbone.Collection.prototype.reset.call(this, models, options);
            update.call(this, models, options);
        },
        // Extended set to update any local-only models with the id from the server.
        set: function(models, options) {
            if(_.isArray(models)) {
                for(var i = 0; i < models.length; ++i) {
                    var attrs = models[i];
                    if('cid' in attrs && !_.isUndefined(attrs['id'])) {
                        var model = this.get(attrs.cid);
                        if(model.isNew()) {
                            model.set('id', attrs.id, {silent: true});
                        }
                    }
                }
            }
            Backbone.Collection.prototype.set.call(this, models, options);
            update.call(this, models, options);
        },

        // Return the url for this collection.
        getUrl: function() {
            return _.isFunction(this.url) ? this.url():this.url;
        }
    });

    return Collection;
});
