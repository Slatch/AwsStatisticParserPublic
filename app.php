<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use FSStats\Application;

error_reporting(E_ERROR | E_PARSE);
(Dotenv::createUnsafeImmutable(__DIR__, '.env'))->load();

try {
    (new Application())->run();
} catch (\Throwable $e) {
    echo '<error>Error while running script: ' . $e->getMessage() . '</error>';
}
