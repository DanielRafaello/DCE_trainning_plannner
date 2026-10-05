<?php

namespace App\Action\Auth;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Auth\RegisterRequest;
use App\Responder\Auth\AuthResponder;
use App\Services\Contracts\Auth\AuthServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class RegisterAction
{
    public function __construct(
        private AuthServiceInterface $authService,
        private AuthResponder $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new RegisterRequest((array)$request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data = $form->safe();
        $result = $this->authService->register(
            name:        $data['name'],
            email:       $data['email'],
            password:    $data['password'],
        );

//        return $this->responder
        //todo
    }
}