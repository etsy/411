"use strict";
define(function(require) {
    var $ = require('jquery'),
        ElementView = require('views/element'),
        ClassMap = require('classmap'),
        Templates = require('templates'),
        Util = require('util'),

        Filter = require('models/filter');


    var FilterView = ElementView.extend({
        modelClass: Filter,
        hide_config: false,
    });

    var classMap = new ClassMap(FilterView);
    FilterView.registerSubclass = $.proxy(ClassMap.prototype.registerSubclass, classMap);
    FilterView.getSubclass = $.proxy(ClassMap.prototype.getSubclass, classMap);

    return FilterView;
});
