<?php

spl_autoload_register(function($class) {
    // Determine the file containing the class and attempt to load it.
    $path = implode('/', array_reverse(explode('_', $class)));
    $class_file_list = [
        sprintf('%s/tests/%s.php', BASE_DIR, $path),
    ];
    foreach($class_file_list as $class_file) {
        if(file_exists($class_file)) {
            include_once($class_file);
            return;
        }
    }
});

require(__DIR__ . '/../phplib/411bootstrap.php');
