<?php

declare(strict_types=1);

namespace App\Responder\Auth;

use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class LogoutResponder extends JsonResponder
{
    public function loggedOut(Response $response): Response
    {
        return $this->json($response, ['logged_out' => true]);
    }
}
