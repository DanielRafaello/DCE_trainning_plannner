<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Exceptions\Validation\ValidationException;
use App\Requests\User\DeleteAccountRequest;
use App\Responder\User\MeResponder;
use App\Services\Contracts\User\UserServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * DELETE /me — permanently delete the own account and all its data (FR-AUTH-10).
 *
 * Auth:     JWT.
 * Body:     { "password" }
 *
 * Response: 204 (no body). The client drops its tokens – refresh tokens are gone with the account,
 *           the access token is rejected on its next use (401 not.found.user).
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           404 not.found.user
 *           409 user.delete.last_admin — the only admin cannot delete themselves
 *           422 Validation failed. — password: user.password.invalid
 */
final readonly class DeleteAccountAction
{
    public function __construct(
        private UserServiceInterface $userService,
        private MeResponder          $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new DeleteAccountRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $this->userService->deleteAccount(
            (int) $request->getAttribute('user_id'),
            (string) $form->safe()['password'],
        );

        return $this->responder->deleted($response);
    }
}
