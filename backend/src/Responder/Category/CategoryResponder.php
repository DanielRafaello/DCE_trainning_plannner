<?php

declare(strict_types=1);

namespace App\Responder\Category;

use App\Domain\Category;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class CategoryResponder extends JsonResponder
{
    /** @param list<Category> $categories */
    public function list(Response $response, array $categories): Response
    {
        return $this->json($response, [
            'categories' => array_map($this->category(...), $categories),
        ]);
    }

    /** @return array<string, mixed> */
    private function category(Category $category): array
    {
        return [
            'id'       => $category->id,
            'slug'     => $category->slug,
            'name'     => $category->name,
            'position' => $category->position,
        ];
    }
}
