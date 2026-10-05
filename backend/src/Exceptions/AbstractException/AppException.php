<?php

declare(strict_types=1);

namespace App\Exceptions\AbstractException;

/**
 * @codeCoverageIgnore
 */
abstract class AppException extends \RuntimeException
{
    abstract public function httpStatus(): int;
}
