<?php

declare(strict_types=1);

namespace App\Exceptions\FileSystemExceptions;

use App\Enums\HttpStatus;
use App\Exceptions\generalExceptions\UnprocessableEntityException;

final class FileWrongMimeTypeException extends UnprocessableEntityException
{
    public function httpStatus(): int
    {
        return HttpStatus::UNPROCESSABLE_ENTITY->value;
    }
}
