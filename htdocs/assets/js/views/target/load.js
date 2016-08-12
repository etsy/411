"use strict";
define(function(require) {
    var TargetView = require('views/target'),
        JiraTargetView = require('views/target/jira');


    /**
     * Loader for Target subclasses.
     */
    TargetView.registerSubclass('jira', JiraTargetView);
});
