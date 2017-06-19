<?php
Hook::register('init.pre', function() {
    TestHelper::setupDB();
});
