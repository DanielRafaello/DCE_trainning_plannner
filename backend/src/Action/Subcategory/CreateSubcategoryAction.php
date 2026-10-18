<?php

declare(strict_types=1);

namespace App\Action\Subcategory;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Subcategory\CreateSubcategoryRequest;
use App\Responder\Subcategory\SubcategoryResponder;
use App\Services\Contracts\Subcategory\SubcategoryServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /subcategories — create a subcategory (FR-SUB-01, FR-SUB-03, FR-SUB-03a).
 *
 * Auth:     JWT.
 * Body:     { "category_id", "name", "color"? } — color `#RRGGBB`; missing = one from the default palette
 *
 * Response: 201 { "subcategory": { "id", "category_id", "name", "color", "created_at", "updated_at" } }
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — category_id: not.found.category;
 *               name: subcategory.name_length / subcategory.name_taken; color: subcategory.color_format
 */
final readonly class CreateSubcategoryAction
{
    public function __construct(
        private SubcategoryServiceInterface $subcategoryService,
        private SubcategoryResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new CreateSubcategoryRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data = $form->safe();
        $subcategory = $this->subcategoryService->create(
            userId:     (int) $request->getAttribute('user_id'),
            categoryId: (int) $data['category_id'],
            name:       (string) $data['name'],
            color:      $data['color'] !== null && $data['color'] !== '' ? (string) $data['color'] : null,
        );

        return $this->responder->one($response, $subcategory, 201);
    }
}
