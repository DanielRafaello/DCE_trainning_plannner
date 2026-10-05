<?php

declare(strict_types=1);

namespace App\Exceptions\FileSystemExceptions;

use App\Enums\HttpStatus;
use App\Exceptions\FileSystemExceptions\AbstractException\FileSystemException;

class FileNotFoundException extends FileSystemException
{
    public function httpStatus(): int
    {
        return HttpStatus::NOT_FOUND->value;
    }
}
