<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', ['.env.development', '.env',]);
$dotenv->load();

// ─── PHP-DI Container ─────────────────────────────────────────────
$containerBuilder = new ContainerBuilder();

// DI compilation is always on – delete var/cache/CompiledContainer.php after adding new bindings
$containerBuilder->enableCompilation(__DIR__ . '/../var/cache');

// ─── add dependecies to DI container ─────────────────────────────────────────────
$containerBuilder->addDefinitions(require __DIR__ . '/dependencies.php');

$container = $containerBuilder->build();

// ─── Slim App s DI containerem ────────────────────────────────────
AppFactory::setContainer($container);
// ─── create applicaton ─────────────────────────────────────────────
$app = AppFactory::create();

// ─── load and setup Middlewares ─────────────────────────────────────────────
$middlewareConfigurator = require __DIR__ . '/middleware.php';
$middlewareConfigurator($app);

// ─── load and setup Routes ─────────────────────────────────────────────
$routesConfigurator = require __DIR__ . '/routes.php';
$routesConfigurator($app);

// ─── run app ─────────────────────────────────────────────
return $app;
