<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use FSStats\Db\Init;

(Dotenv::createUnsafeImmutable(__DIR__, '.env'))->load();

(new Init())->init();

// usage:
// docker run -it --rm --name my-running-script -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:8.2-cli php build_response.php
