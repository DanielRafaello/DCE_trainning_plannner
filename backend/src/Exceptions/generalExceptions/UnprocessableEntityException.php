<?php

declare(strict_types=1);

namespace App\Exceptions\generalExceptions;

use App\Enums\HttpStatus;
use App\Exceptions\AbstractException\AppException;

class UnprocessableEntityException extends AppException
{
    public function httpStatus(): int
    {
        return HttpStatus::UNPROCESSABLE_ENTITY->value;
    }
}
