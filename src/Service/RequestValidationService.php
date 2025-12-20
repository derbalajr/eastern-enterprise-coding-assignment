<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidationService
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    public function getJsonData(Request $request): ?array
    {
        try {
            return json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    public function validate($object): ConstraintViolationListInterface
    {
        return $this->validator->validate($object);
    }

    public function formatErrors(ConstraintViolationListInterface $errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }
        return $errorMessages;
    }
}

