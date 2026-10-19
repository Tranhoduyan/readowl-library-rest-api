<?php

// Chay: php -S localhost:8000 -t public router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/api/v1' || strpos($path, '/api/v1/') === 0) {
    require __DIR__ . '/api/index.php';
    return true;
}

// De may chu PHP phuc vu HTML, CSS, JS trong public.
return false;
