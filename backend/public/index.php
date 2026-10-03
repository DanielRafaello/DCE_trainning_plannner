<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../app/bootstrap.php';       // return app, that is already configures using routes and middleware

$app->run();
