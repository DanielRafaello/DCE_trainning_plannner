<?php

declare(strict_types=1);

namespace App\Responder\Subcategory;

use App\Domain\Subcategory;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class SubcategoryResponder extends JsonResponder
{
    /** @param list<Subcategory> $subcategories */
    public function list(Response $response, array $subcategories): Response
    {
        return $this->json($response, [
            'subcategories' => array_map($this->subcategory(...), $subcategories),
        ]);
    }

    public function one(Response $response, Subcategory $subcategory, int $status = 200): Response
    {
        return $this->json($response, ['subcategory' => $this->subcategory($subcategory)], $status);
    }

    public function deleted(Response $response): Response
    {
        return $response->withStatus(204);
    }

    /** @return array<string, mixed> */
    private function subcategory(Subcategory $subcategory): array
    {
        return [
            'id'          => $subcategory->id,
            'category_id' => $subcategory->categoryId,
            'name'        => $subcategory->name,
            'color'       => $subcategory->color,
            'created_at'  => $subcategory->createdAt,
            'updated_at'  => $subcategory->updatedAt,
        ];
    }
}
