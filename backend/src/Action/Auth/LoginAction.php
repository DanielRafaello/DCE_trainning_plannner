<?php

declare(strict_types=1);

namespace App\Action\Auth;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Auth\LoginRequest;
use App\Responder\Auth\AuthResponder;
use App\Services\Contracts\Auth\AuthServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
/**
 * POST /auth/login — sign in with e-mail and password.
 *
 * Auth:     none (public).
 * Body:     { "email", "password" }
 *
 * Steps:
 *   1. Validate the body (LoginRequest): both fields present, e-mail well-formed.
 *   2. AuthService::login checks the password against the stored hash and
 *      issues a new JWT access token plus a new refresh token. Earlier
 *      sessions of the account stay valid.
 *
 * Response: 200 { "token", "refresh_token", "user": { "id", "name", "email", "role" } }
 * Errors:   422 Validation failed.
 *           401 not.found.user.invalid.credentials — unknown e-mail or wrong
 *               password (deliberately the same error for both).
 */

final class LoginAction
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
        private readonly AuthResponder $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new LoginRequest ((array) $request->getParsedBody());

        if ($form->fails()){
            throw new ValidationException($form->errors());
        }

        $data = $form->safe();
        $result = $this->authService->login($data['email'], $data['password']);

        return $this->responder->respond($response, $result['token'], $result['refresh_token'], $result['user'], 200);
    }
}