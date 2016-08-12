"use strict";
define(function(require) {
    var _ = require('underscore'),
        View = require('view'),
        Templates = require('templates');


    /**
     * Base navbar View
     */
    var NavbarView = View.extend({
        tagName: 'div',
        template: Templates['navbar'],

        title: '',
        search: false,
        // Definition for links, sidelinks and searchlinks.
        links: null,
        sidelinks: null,
        searchlinks: null,

        _render: function() {
            this.$el.html(this.template({
                title: this.title,
                search: this.search,
                links: this.links,
                sidelinks: this.sidelinks,
                searchlinks: this.searchlinks,
                dropdown: !_.isEmpty(this.links) || !_.isEmpty(this.sidelinks),
            }));

            if(this.links) {
                for(var i = 0; i < this.links.length; ++i) {
                    this.App.registerKbdShortcut('shift+' + (i+1), $.proxy(this.click, this, '.navbar-link-' + i), this.links[i].name);
                }
            }

            this.App.registerKbdShortcut('s', $.proxy(this.click, this, '.search-button'), 'Open search dialog');
            this.App.registerKbdShortcut('c', $.proxy(this.click, this, '.create-button'), 'Create a new object');
        },
        click: function(sel) {
            this.$(sel).click();
        },
        destroy: function() {
            View.prototype.destroy.call(this, true);
        }
    });

    return NavbarView;
});
