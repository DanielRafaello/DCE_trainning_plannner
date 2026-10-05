<?php

declare(strict_types=1);

namespace App\Exceptions\FileSystemExceptions;

use App\Enums\HttpStatus;
use App\Exceptions\FileSystemExceptions\AbstractException\FileSystemException;

class FileInvalidPathException extends FileSystemException
{
    public function httpStatus(): int
    {
        return HttpStatus::BAD_REQUEST->value;
    }
}
