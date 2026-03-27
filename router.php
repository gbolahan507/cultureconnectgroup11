<?php
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// If file exists as-is, serve it
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false;
}

// Try appending .php
$phpFile = __DIR__ . $path . '.php';
if (file_exists($phpFile)) {
    chdir(dirname($phpFile));
    require $phpFile;
    return;
}

// Default
return false;
