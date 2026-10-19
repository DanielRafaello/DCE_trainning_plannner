<?php

declare(strict_types=1);

namespace App\Action\Subcategory;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Subcategory\UpdateSubcategoryRequest;
use App\Responder\Subcategory\SubcategoryResponder;
use App\Services\Contracts\Subcategory\SubcategoryServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * PATCH /subcategories/{id} — rename and/or recolor (FR-SUB-05). The category cannot be changed.
 *
 * Auth:     JWT.
 * Body:     { "name"?, "color"? } — a missing (or null) field keeps the current value
 *
 * Response: 200 { "subcategory": { "id", "category_id", "name", "color", "created_at", "updated_at" } }
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           404 not.found.subcategory — also for another user's subcategory.
 *           422 Validation failed. — name: subcategory.name_length / subcategory.name_taken;
 *               color: subcategory.color_format
 */
final readonly class UpdateSubcategoryAction
{
    public function __construct(
        private SubcategoryServiceInterface $subcategoryService,
        private SubcategoryResponder        $responder,
    ) {
    }

    /** @param array{id: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $form = new UpdateSubcategoryRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data = $form->safe();
        $subcategory = $this->subcategoryService->update(
            userId:        (int) $request->getAttribute('user_id'),
            subcategoryId: (int) $args['id'],
            name:          $data['name'] !== null ? (string) $data['name'] : null,
            color:         $data['color'] !== null ? (string) $data['color'] : null,
        );

        return $this->responder->one($response, $subcategory);
    }
}
