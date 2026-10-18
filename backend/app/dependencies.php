<?php

declare(strict_types=1);

use App\Middleware\CorsMiddleware;
use App\Repositories\CategoryRepository;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\DayNoteRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\RefreshTokenRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use App\Repositories\Contracts\SubcategoryRepositoryInterface;
use App\Repositories\Contracts\TodoListRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\DayNoteRepository;
use App\Repositories\EventRepository;
use App\Repositories\RefreshTokenRepository;
use App\Repositories\StatisticsRepository;
use App\Repositories\SubcategoryRepository;
use App\Repositories\TodoListRepository;
use App\Repositories\UserRepository;
use App\Services\Admin\AdminUserService;
use App\Services\Auth\AuthService;
use App\Services\Category\CategoryService;
use App\Services\Contracts\Admin\AdminUserServiceInterface;
use App\Services\Contracts\Auth\AuthServiceInterface;
use App\Services\Contracts\Category\CategoryServiceInterface;
use App\Services\Contracts\DayNote\DayNoteServiceInterface;
use App\Services\Contracts\Subcategory\SubcategoryServiceInterface;
use App\Services\Contracts\TodoList\TodoListServiceInterface;
use App\Services\Contracts\User\UserServiceInterface;
use App\Services\DayNote\DayNoteService;
use App\Services\Subcategory\SubcategoryService;
use App\Services\TodoList\TodoListService;
use App\Services\User\UserService;
use App\Services\JWT\JwtService;
use App\Settings\Settings;
use App\Settings\SettingsFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

/*
 * Where to put a class to the container?
 * - when binding an Interface to a specific Class
 * - when class in constructors need primitive values (example: string from env)
 * - we need specific config or calling method after calling constructor
 */


return [
    ResponseFactoryInterface::class => function (): ResponseFactoryInterface {
        return AppFactory::determineResponseFactory();
    },


    Settings::class => function (): Settings {
        return new SettingsFactory()();
    },

    Connection::class => function (ContainerInterface $c): Connection {
        /** @var Settings $s */
        $s = $c->get(Settings::class);

        return DriverManager::getConnection([
            'driver'   => $s->get('db.driver'),
            'host'     => $s->get('db.host'),
            'port'     => $s->get('db.port'),
            'dbname'   => $s->get('db.dbname'),
            'user'     => $s->get('db.user'),
            'password' => $s->get('db.password'),
        ]);
    },
    // ─── Logging (Monolog, PSR-3) ────────────────────────────────────
    // php://stderr — under this image's Apache+mod_php setup that lands in
    // the vhost's ErrorLog (/var/log/apache2/error.log inside the container,
    // NOT `docker compose logs app` — verified empirically, Apache captures
    // worker stderr into its own error log rather than the container's
    // stdout/stderr since vhost.conf sets an explicit ErrorLog file). Still a
    // real improvement over scattered error_log() calls: one structured
    // (Monolog line-JSON-ish) format, one place to look.
    LoggerInterface::class => function (): LoggerInterface {
        $logger = new Logger('app');

        // Handler for writing to stderr
        $handler = new StreamHandler('php://stderr', Level::Debug);

        // launch JSON formatting
        $formatter = new JsonFormatter();
        $formatter->includeStacktraces(); // logged exceptions keep their trace
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        // Same records into var/log/app-YYYY-MM-DD.log, one file per day, 14 days kept.
        // Development: the project folder on the host. Production: the backend_logs volume.
        $fileHandler = new RotatingFileHandler(__DIR__ . '/../var/log/app.log', 14, Level::Debug);
        $fileHandler->setFormatter($formatter);

        // A log file that cannot be written must never turn an error response into a crash
        $logger->pushHandler(new WhatFailureGroupHandler([$fileHandler]));

        return $logger;
    },

    // ─── CORS (needs the primitive allowed-origin) ───────────────────
    CorsMiddleware::class => function (ContainerInterface $c): CorsMiddleware {
    /** @var Settings $s */
        $s = $c->get(Settings::class);

        return new CorsMiddleware($c->get(ResponseFactoryInterface::class), $s->get('app.frontend_url'));
    },

    // ─── JWT ────────────────────────────────────────────────────────
    JwtService::class => function (ContainerInterface $c): JwtService {
        /** @var Settings $s */
        $s = $c->get(Settings::class);
        return new JwtService($s->get('jwt.secret'), $s->get('jwt.ttl_seconds'));
    },

    AuthServiceInterface::class => function (ContainerInterface $c): AuthServiceInterface {
        /** @var Settings $s */
        $s = $c->get(Settings::class);

        return new AuthService(
            $c->get(UserRepositoryInterface::class),
            $c->get(RefreshTokenRepositoryInterface::class),
            $c->get(JwtService::class),
            $s->get('jwt.refresh_ttl_seconds'),
        );
    },


    // ─── Repositories ─────────────────────────────────────────────
    UserRepositoryInterface::class         => \DI\autowire(UserRepository::class),
    RefreshTokenRepositoryInterface::class => \DI\autowire(RefreshTokenRepository::class),
    CategoryRepositoryInterface::class     => \DI\autowire(CategoryRepository::class),
    SubcategoryRepositoryInterface::class  => \DI\autowire(SubcategoryRepository::class),
    EventRepositoryInterface::class        => \DI\autowire(EventRepository::class),
    DayNoteRepositoryInterface::class      => \DI\autowire(DayNoteRepository::class),
    TodoListRepositoryInterface::class     => \DI\autowire(TodoListRepository::class),
    StatisticsRepositoryInterface::class   => \DI\autowire(StatisticsRepository::class),

    // ─── Services ─────────────────────────────────────────────────
    CategoryServiceInterface::class        => \DI\autowire(CategoryService::class),
    SubcategoryServiceInterface::class     => \DI\autowire(SubcategoryService::class),
    UserServiceInterface::class            => \DI\autowire(UserService::class),
    TodoListServiceInterface::class        => \DI\autowire(TodoListService::class),
    DayNoteServiceInterface::class         => \DI\autowire(DayNoteService::class),
    AdminUserServiceInterface::class       => \DI\autowire(AdminUserService::class),

];