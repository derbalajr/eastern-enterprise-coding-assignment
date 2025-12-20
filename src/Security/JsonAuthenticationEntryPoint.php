<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class JsonAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(
            [
                'error' => 'Unauthorized',
                'message' => 'Authentication required. Please provide valid Basic Auth credentials.',
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                'WWW-Authenticate' => 'Basic realm="Symfony Assessment API"',
            ]
        );
    }
}

