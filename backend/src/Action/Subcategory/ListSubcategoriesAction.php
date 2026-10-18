<?php

declare(strict_types=1);

namespace App\Action\Subcategory;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Subcategory\ListSubcategoriesRequest;
use App\Responder\Subcategory\SubcategoryResponder;
use App\Services\Contracts\Subcategory\SubcategoryServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /subcategories?category= — the signed-in user's subcategories (FR-SUB-04).
 *
 * Auth:     JWT.
 * Query:    category (optional) — only this category.
 *
 * Response: 200 { "subcategories": [ { "id", "category_id", "name", "color", "created_at", "updated_at" } ] }
 *           ordered by category position, then name
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — category is not a number or does not exist.
 */
final readonly class ListSubcategoriesAction
{
    public function __construct(
        private SubcategoryServiceInterface $subcategoryService,
        private SubcategoryResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new ListSubcategoriesRequest($request->getQueryParams());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $category = $form->safe()['category'];

        $subcategories = $this->subcategoryService->list(
            (int) $request->getAttribute('user_id'),
            $category !== null && $category !== '' ? (int) $category : null,
        );

        return $this->responder->list($response, $subcategories);
    }
}
