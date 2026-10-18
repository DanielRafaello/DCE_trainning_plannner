<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Exceptions\Validation\ValidationException;
use App\Requests\User\ChangePasswordRequest;
use App\Responder\Auth\AuthResponder;
use App\Services\Contracts\User\UserServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * PUT /me/password — change the password (FR-AUTH-09).
 *
 * Auth:     JWT.
 * Body:     { "current_password", "password", "password_confirm" }
 *
 * Steps:
 *   1. Validate the body (ChangePasswordRequest).
 *   2. UserService::changePassword verifies the current password, stores the new hash and revokes
 *      ALL refresh tokens of the user, so other devices are logged out.
 *   3. A fresh token pair is returned – the client replaces its tokens and stays logged in.
 *
 * Response: 200 { "token", "refresh_token", "user": { "id", "name", "email", "role", "timezone", "created_at" } }
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           404 not.found.user
 *           422 Validation failed. — current_password: user.password.invalid
 */
final readonly class ChangePasswordAction
{
    public function __construct(
        private UserServiceInterface $userService,
        private AuthResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new ChangePasswordRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data   = $form->safe();
        $result = $this->userService->changePassword(
            userId:          (int) $request->getAttribute('user_id'),
            currentPassword: (string) $data['current_password'],
            newPassword:     (string) $data['password'],
        );

        return $this->responder->respond($response, $result['token'], $result['refresh_token'], $result['user'], 200);
    }
}
