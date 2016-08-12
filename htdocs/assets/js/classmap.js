"use strict";
define(function(require) {
    var ClassMap = function(def) {
        this.defaultClass = def;
        this.classMap = {};
    };

    ClassMap.prototype.registerSubclass = function(name, cls) {
        this.classMap[name] = cls;
    };

    ClassMap.prototype.getSubclass = function(name) {
        return name in this.classMap ? this.classMap[name]:this.defaultClass;
    };

    ClassMap.prototype.getSubclasses = function() {
        return this.classMap;
    };

    return ClassMap;
});
