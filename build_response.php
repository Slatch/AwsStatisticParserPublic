<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use FSStats\BuildResponseApplication;

(Dotenv::createUnsafeImmutable(__DIR__, '.env'))->load();

(new BuildResponseApplication())->run();

// usage:
// docker run -it --rm --name my-running-script -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:8.2-cli php build_response.php
