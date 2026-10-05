<?php

declare(strict_types=1);

namespace App\Enums;

enum HttpStatus: int
{
    case BAD_REQUEST = 400;          // 400
    case UNAUTHORIZED = 401;         // 401
    case PAYMENT_REQUIRED = 402;     // 402
    case FORBIDDEN = 403;            // 403
    case NOT_FOUND = 404;            // 404
    case CONFLICT = 409;             // 409

    case UNPROCESSABLE_ENTITY = 422; // 422
    case INTERNAL_SERVER_ERROR = 500;// 500
    case SERVICE_UNAVAILABLE = 503;  // 503
}
