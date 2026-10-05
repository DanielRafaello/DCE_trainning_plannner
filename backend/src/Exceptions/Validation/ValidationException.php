<?php

declare(strict_types=1);

namespace App\Exceptions\Validation;

use App\Exceptions\generalExceptions\UnprocessableEntityException;

class ValidationException extends UnprocessableEntityException
{
    /** @param array<string, string> $errors */
    public function __construct(
        public readonly array $errors,
        string $message = 'Validation failed.',
    ) {
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
