<?php

declare(strict_types=1);

namespace App\Responder;

use Psr\Http\Message\ResponseInterface as Response;

abstract class JsonResponder
{
    /** @param array<string, mixed> $data
     * @throws \JsonException
     */
    protected function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
