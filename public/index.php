<?php
require dirname(__DIR__) . '/app/Config/config.php';
require dirname(__DIR__) . '/app/Config/database.php';

spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . "/app/Core/{$class}.php",
        BASE_PATH . "/app/Controllers/{$class}.php",
        BASE_PATH . "/app/Models/{$class}.php",
        BASE_PATH . "/app/Middleware/{$class}.php",
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

App::run();
