"use strict";
define(function(require) {
    var $ = require('jquery'),
        Handlebars = require('handlebars'),
        Util = require('util');

    require('tablesorter');


    // Customize tablesorter theme.
    var bstheme = $.tablesorter.themes.bootstrap;
    bstheme.table = 'table table-condensed table-striped';
    bstheme.icons = 'icon-white';
    bstheme.iconSortNone = 'icon-minus glyphicon glyphicon-minus';

    // Handlebars helper. Convert string to title case.
    Handlebars.registerHelper('titlecase', function(str) {
        str = str + '';
        return str[0].toUpperCase() + str.substr(1);
    });
    // Handlebars helper. Convert time to string.
    Handlebars.registerHelper('time', function(str) {
        return Util.formatTime(str);
    });
    // Handlebars helper. Convert timestamp to UTC string.
    Handlebars.registerHelper('date', function(str) {
        return Util.formatDate(str);
    });
    // Handlebars helper. Triggers if the two arguments are equal.
    Handlebars.registerHelper('ifeq', function(a, b, options) {
        return a == b ? options.fn(this):options.inverse(this);
    });
    // Handlebars helper. Triggers if the two arguments are not equal.
    Handlebars.registerHelper('ifneq', function(a, b, options) {
        return a != b ? options.fn(this):options.inverse(this);
    });
    // Handlebars helper. Retrieves a value from the dictionary with a given key.
    Handlebars.registerHelper('dict', function(object, options) {
        var val = '';
        if('default' in options.hash) {
            val = options.hash.default;
        }
        if(options.hash.key in object) {
            val = object[options.hash.key];
        }
        return val;
    });
    // Handlebars helper. Logs a variable to the console.
    Handlebars.registerHelper('log', function(text) {
        return console.log(text);
    });

    // Tablesorter parser. Sorts on whether the column has data.
    $.tablesorter.addParser({
        id: 'bool',
        is: function() {
            return false;
        },
        format: function(s, table, cell, cellIndex) {
            return s.length;
        },
        parsed: true,
        type: 'numeric'
    });
    // Tablesorter parser. Sorts on whether the checkbox is checked.
    $.tablesorter.addParser({
        id: 'check',
        is: function() {
            return false;
        },
        format: function(s, table, cell, cellIndex) {
            var inp = $(cell).find('input').get(0);
            return inp ? inp.checked:false;
        },
        parsed: true,
        type: 'numeric'
    });
    // Tablesorter parser. Sorts on datetime.
    $.tablesorter.addParser({
        id: 'datetime',
        is: function (s) {
            return false;
        },
        format: function (s) {
            return Date.parse(s);
        },
        type: 'numeric'
    });
});
