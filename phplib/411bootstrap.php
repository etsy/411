<?php

namespace FOO;

/**
 * 411 entrypoint
 * Initializes the environment and sets up autoloaders.
 * @package FOO
 */

ini_set('max_execution_time', 600);
ini_set('memory_limit', '256M');

// Set up error handlers.
error_reporting(E_ALL);
set_error_handler(function($errno, $str, $file, $line) {
    Logger::backtrace("Errno $errno", $str, $file, $line, null, 2);
});

date_default_timezone_set("UTC");

define('BASE_DIR', realpath(__DIR__ . '/..'));
define('VERSION', '1.0.0');

// Set up autoloader for our classes.
spl_autoload_register(function($class) {
    // Hack to find Finders.
    if(strlen($class) > 6 && strrpos($class, 'Finder', -6) !== false) {
        $class = substr($class, 0, -6);
    }
    // Determine the autoload path from the classname.
    if(strpos($class, '\\') !== false) {
        $class_parts = explode('\\', $class);
        if($class_parts[0] !== 'FOO') {
            return;
        }
        $class = $class_parts[1];
    }

    // Determine the file containing the class and attempt to load it.
    $path = implode('/', array_reverse(explode('_', $class)));
    $class_file_list = [
        sprintf('%s/extlib/%s.php', BASE_DIR, $path),
        sprintf('%s/phplib/%s.php', BASE_DIR, $path),
    ];
    foreach($class_file_list as $class_file) {
        if(file_exists($class_file)) {
            include_once($class_file);
            return;
        }
    }
});
require(BASE_DIR . '/config.php');
if(file_exists(BASE_DIR . '/hook.php')) {
    include(BASE_DIR . '/hook.php');
}
require(BASE_DIR . '/vendor/autoload.php');

// Init static attributes.
Hook::call('init.pre');
Config::init($config);
Logger::init();
DB::init();
Cookie::init();
Nonce::init();
Hook::call('init.post');
