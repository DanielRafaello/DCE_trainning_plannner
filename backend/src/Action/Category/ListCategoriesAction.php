<?php

declare(strict_types=1);

namespace App\Action\Category;

use App\Responder\Category\CategoryResponder;
use App\Services\Contracts\Category\CategoryServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /categories — the fixed categories (FR-CAT-03).
 *
 * Auth:     JWT.
 * Body:     none.
 *
 * Response: 200 { "categories": [ { "id", "slug", "name", "position" } ] } in display order
 * Errors:   401 Missing access token. / Invalid or expired token.
 */
final readonly class ListCategoriesAction
{
    public function __construct(
        private CategoryServiceInterface $categoryService,
        private CategoryResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        return $this->responder->list($response, $this->categoryService->list());
    }
}
