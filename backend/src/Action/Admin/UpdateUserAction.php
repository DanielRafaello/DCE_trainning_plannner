<?php

declare(strict_types=1);

namespace App\Action\Admin;

use App\Domain\Role;
use App\Exceptions\Validation\ValidationException;
use App\Requests\Admin\UpdateUserRequest;
use App\Responder\Admin\AdminUserResponder;
use App\Services\Contracts\Admin\AdminUserServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * PATCH /admin/users/{id} — change role and/or block/unblock (FR-ADM-02).
 *
 * Auth:     JWT + admin role (AdminOnlyMiddleware).
 * Body:     { "role"?: "user"|"admin", "blocked"?: true|false } — at least one
 *
 * Steps:
 *   1. role present    → AdminUserService::changeRole (takes effect on the user's next request)
 *   2. blocked present → AdminUserService::setBlocked; blocking revokes the user's refresh tokens,
 *      every request, login and refresh then return 403 auth.account_blocked
 *
 * Response: 200 { "user": { "id", "name", "email", "role", "timezone", "blocked_at", "created_at" } }
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           403 auth.forbidden.admin_only
 *           404 not.found.user
 *           409 admin.cannot_demote_self / admin.cannot_block_self / admin.last_admin
 *           422 Validation failed.
 */
final readonly class UpdateUserAction
{
    public function __construct(
        private AdminUserServiceInterface $adminUserService,
        private AdminUserResponder        $responder,
    ) {
    }

    /** @param array{id: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $form = new UpdateUserRequest($body);

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $role    = ($body['role'] ?? null) !== null ? Role::from((string) $body['role']) : null;
        $blocked = array_key_exists('blocked', $body)
            ? filter_var($body['blocked'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        if ($role === null && $blocked === null) {
            throw new ValidationException(['role' => 'admin.nothing_to_update']);
        }

        $adminId = (int) $request->getAttribute('user_id');
        $userId  = (int) $args['id'];

        if ($role !== null) {
            $user = $this->adminUserService->changeRole($adminId, $userId, $role);
        }
        if ($blocked !== null) {
            $user = $this->adminUserService->setBlocked($adminId, $userId, $blocked);
        }

        return $this->responder->one($response, $user);
    }
}
