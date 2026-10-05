<?php

declare(strict_types=1);

namespace App\Exceptions\FileSystemExceptions\AbstractException;

use App\Exceptions\AbstractException\AppException;

/**
 * @codeCoverageIgnore
 */
abstract class FileSystemException extends AppException
{
    abstract public function httpStatus(): int;
}
