"use strict";
define(function(require) {
    var $ = require('jquery'),
        ElementView = require('views/element'),
        ClassMap = require('classmap'),
        Templates = require('templates'),
        Util = require('util'),

        Target = require('models/target');


    var TargetView = ElementView.extend({
        modelClass: Target,
    });

    var classMap = new ClassMap(TargetView);
    TargetView.registerSubclass = $.proxy(ClassMap.prototype.registerSubclass, classMap);
    TargetView.getSubclass = $.proxy(ClassMap.prototype.getSubclass, classMap);

    return TargetView;
});
