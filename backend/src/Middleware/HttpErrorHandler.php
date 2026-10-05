<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\AbstractException\AppException;
use App\Exceptions\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler;

class HttpErrorHandler extends ErrorHandler
{
    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;

        $errors = $exception instanceof ValidationException ? $exception->getErrors() : null;

        [$statusCode, $message] = match (true) {
            $exception instanceof AppException  => [$exception->httpStatus(), $exception->getMessage()],
            $exception instanceof HttpException => [$exception->getCode(), $exception->getMessage()],
            default                             => [500, $this->displayErrorDetails // display error is turned on in dev mode
                ? $exception->getMessage()
                : 'Server error'],
        };

        return $this->buildResponse($this->request, $statusCode, $message, $errors);
    }

    /** @param array<string, string>|null $errors */
    public function buildResponse(
        ServerRequestInterface $request,
        int $statusCode,
        string $message,
        ?array $errors = null,
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse($statusCode);

        $body = [
            'error'   => true,
            'status'  => $statusCode,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        $response->getBody()->write(json_encode($body));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
