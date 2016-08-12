"use strict";
define(function(require) {
    var $ = require('jquery'),
        Backbone = require('backbone');


    /**
     * Base Model class
     */
    var Model = Backbone.Model.extend({
        /**
         * Destroy the model.
         * @param {boolean} options.defer - Set a bit on the model that indicates it should be deleted. This makes it simplier to do batch updates via the Collection class.
         * @param {boolean} options.soft - Set to only delete the model on the client side. (In other words, don't send a DELETE to the server.
         */
        destroy: function(options) {
            options = options || {};

            // Defer delete
            if(options.defer && !this.isNew()) {
                this.set('_delete', true);
            // Soft delete
            } else if(options.soft) {
                this.stopListening();
                this.trigger('destroy', this, this.collection, options);
                this.unset(this.idAttribute, {silent: true});
            } else {
                var error = options.error;
                var collection = this.collection;

                // If the delete fails, reinsert into the collection.
                options.error = $.proxy(function() {
                    if(collection) {
                        collection.add(this);
                    }
                }, this);
                return Backbone.Model.prototype.destroy.call(this, options);
            }
        }
    });

    return Model;
});
