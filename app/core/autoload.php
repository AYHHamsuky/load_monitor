<?php
// File: /app/Core/Autoload.php

spl_autoload_register(function ($class) {

    // Only autoload App\ classes
    if (strpos($class, 'App\\') !== 0) {
        return;
    }

    $path = __DIR__ . '/../' . str_replace('App\\', '', $class);
    $path = str_replace('\\', '/', $path) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});
