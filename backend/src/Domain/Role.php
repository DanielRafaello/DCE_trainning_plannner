<?php

declare(strict_types=1);

namespace App\Domain;

enum Role: string
{
    case USER = 'user';
    case ADMIN = 'admin';

}
