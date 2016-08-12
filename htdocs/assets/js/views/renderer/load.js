"use strict";
define(function(require) {
    var Renderer = require('views/renderer'),
        IPRenderer = require('views/renderer/ip'),
        MACRenderer = require('views/renderer/mac'),
        LinkRenderer = require('views/renderer/link');


    /**
     * Loader for Renderer subclasses.
     */
    Renderer.registerSubclass('ip', IPRenderer);
    Renderer.registerSubclass('mac', MACRenderer);
    Renderer.registerSubclass('link', LinkRenderer);
});
