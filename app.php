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

// usage:
// docker run -it --rm --name my-running-script -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:8.2-cli php parse_statistic.php

// speed
// 5mb in sec


// current storage:
// 22000 object
// 1.3 TB


// first
// 2024-03-05

// last
// 2024-04-02
