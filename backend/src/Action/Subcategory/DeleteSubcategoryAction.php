<?php

declare(strict_types=1);

namespace App\Action\Subcategory;

use App\Responder\Subcategory\SubcategoryResponder;
use App\Services\Contracts\Subcategory\SubcategoryServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * DELETE /subcategories/{id} — delete a subcategory that no event uses (FR-SUB-06).
 *
 * Auth:     JWT.
 * Body:     none.
 *
 * Response: 204 (no body)
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           404 not.found.subcategory — also for another user's subcategory.
 *           409 subcategory.has_events:<count> — events still reference it.
 */
final readonly class DeleteSubcategoryAction
{
    public function __construct(
        private SubcategoryServiceInterface $subcategoryService,
        private SubcategoryResponder        $responder,
    ) {
    }

    /** @param array{id: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->subcategoryService->delete((int) $request->getAttribute('user_id'), (int) $args['id']);

        return $this->responder->deleted($response);
    }
}
