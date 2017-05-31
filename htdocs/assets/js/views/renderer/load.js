"use strict";
define(function(require) {
    var Renderer = require('views/renderer'),
        IPRenderer = require('views/renderer/ip'),
        MACRenderer = require('views/renderer/mac'),
        StacktraceRenderer = require('views/renderer/stacktrace'),
        LinkRenderer = require('views/renderer/link');


    /**
     * Loader for Renderer subclasses.
     */
    Renderer.registerSubclass('ip', IPRenderer);
    Renderer.registerSubclass('mac', MACRenderer);
    Renderer.registerSubclass('stacktrace', StacktraceRenderer);
    Renderer.registerSubclass('link', LinkRenderer);
});
