<?php

declare(strict_types=1);

namespace App\Action\Admin;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Admin\ListUsersRequest;
use App\Responder\Admin\AdminUserResponder;
use App\Services\Contracts\Admin\AdminUserServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /admin/users?search=&page=&per_page= — paginated user list (FR-ADM-01).
 *
 * Auth:     JWT + admin role (AdminOnlyMiddleware).
 * Query:    search (optional) substring of name or email, case-insensitive
 *           page (default 1), per_page (default 20, max 100)
 *
 * Response: 200 { "users": [ { "id", "name", "email", "role", "timezone", "blocked_at", "created_at" } ],
 *                 "total", "page", "per_page" }
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           403 auth.forbidden.admin_only
 *           422 Validation failed.
 */
final readonly class ListUsersAction
{
    public function __construct(
        private AdminUserServiceInterface $adminUserService,
        private AdminUserResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new ListUsersRequest($request->getQueryParams());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data = $form->safe();
        $page = $this->adminUserService->list(
            search:  $data['search'] !== null ? (string) $data['search'] : null,
            page:    ($data['page'] ?? '') !== '' ? (int) $data['page'] : 1,
            perPage: ($data['per_page'] ?? '') !== '' ? (int) $data['per_page'] : AdminUserServiceInterface::DEFAULT_PER_PAGE,
        );

        return $this->responder->list($response, $page);
    }
}
