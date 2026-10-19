<?php

declare(strict_types=1);

namespace App\Responder\Auth;

use App\Infrastructure\AuthCookies;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class LogoutResponder extends JsonResponder
{
    public function __construct(
        private readonly AuthCookies $cookies,
    ) {
    }

    public function loggedOut(Response $response): Response
    {
        return $this->json($this->cookies->withoutTokens($response), ['logged_out' => true]);
    }
}
