<?php

require 'vendor/autoload.php';
define('BASEDIR', __DIR__ . '/');


$globalConf = require BASEDIR . "conf.php";

if ($globalConf['debug'] == true) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

define("ROOTPATH", $globalConf['webRootPath']);
define("DOMAIN", $globalConf['domain']);

define("MAX_INPUT_LENGTH", 255 - 128); /* you cant make this bigger then 255 with out changing the cap to to the db */
define("MAX_INPUT_LENGTH_PASSWORD", 16 - 8); /* you cant make this bigger then 16 with out changing the cap to to the db */

define("IMAGE_EXTENTIONS", $globalConf['IMAGE_EXTENTIONS']);
define("VIDEO_EXTENTIONS", $globalConf['VIDEO_EXTENTIONS']);
define("AUDIO_EXTENTIONS", $globalConf['AUDIO_EXTENTIONS']);

ini_set('session.cookie_lifetime', $globalConf['sessionLifeTime']);
ini_set("memory_limit", $globalConf['memoryLimit']);


// Autoloader to automatically load class files based on their namespace and class name
spl_autoload_register(function ($class) {
    $prefix = 'Modules\\';
    $base_dir = BASEDIR . '/modules/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Handle special case for ModuleBase.php
    if ($relative_class === 'Module') {
        $file = $base_dir . 'ModuleBase.php';
    }


    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Function to load all modules from the /modules directory
function loadModules($modulesDir = __DIR__ . '/modules')
{
    $modules = [];
    foreach (scandir($modulesDir) as $dir) {
        if ($dir === '.' || $dir === '..' || is_file($modulesDir . '/' . $dir)) {
            continue;
        }

        $mainFile = $modulesDir . '/' . $dir . '/Main.php';

        if (file_exists($mainFile)) {
            include_once $mainFile;
            $className = 'Modules\\' . $dir . '\\Main';
            if (class_exists($className)) {
                $modules[] = new $className();
            }
        }
    }
    return $modules;
}