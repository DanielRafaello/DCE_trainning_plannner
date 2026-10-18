<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Exceptions\Validation\ValidationException;
use App\Requests\User\UpdateProfileRequest;
use App\Responder\User\MeResponder;
use App\Services\Contracts\User\UserServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * PATCH /me — update the own profile (FR-AUTH-08).
 *
 * Auth:     JWT.
 * Body:     { "name"?, "email"?, "timezone"? } — a missing (or null) field keeps the current value
 *
 * Response: 200 { "user": { "id", "name", "email", "role", "timezone", "created_at" } }
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           404 not.found.user
 *           422 Validation failed. — email: auth.register.email_taken; name: user.name_length;
 *               timezone: user.timezone_invalid
 */
final readonly class UpdateProfileAction
{
    public function __construct(
        private UserServiceInterface $userService,
        private MeResponder          $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new UpdateProfileRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data = $form->safe();
        $user = $this->userService->updateProfile(
            userId:   (int) $request->getAttribute('user_id'),
            name:     $data['name'] !== null ? (string) $data['name'] : null,
            email:    $data['email'] !== null ? (string) $data['email'] : null,
            timezone: $data['timezone'] !== null ? (string) $data['timezone'] : null,
        );

        return $this->responder->respond($response, $user);
    }
}
