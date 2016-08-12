<?php

FOO\Hook::register('init.pre', function() {
    FOO\Config::setData($config);
});
    print "HII\n";
