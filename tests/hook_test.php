<?php

FOO\Hook::register('init.pre', function() {
    TestHelper::setupDB();
});
