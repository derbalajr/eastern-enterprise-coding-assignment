<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\DTO\CreateCountryRequest;
use App\DTO\UpdateCountryRequest;
use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Service\CountryMapperService;
use App\Service\RequestValidationService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

#[Route('countries')]
#[OA\Tag(name: 'Countries')]
class CountryController extends AbstractController
{
    private const SERIALIZATION_GROUPS = ['groups' => ['country:read']];

    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestValidationService $validationService,
        private readonly CountryMapperService $mapperService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/countries/list',
        summary: 'Get all countries',
        description: 'Retrieve a paginated list of all countries',
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number (default: 1)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Items per page (default: 50, max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50, minimum: 1, maximum: 100)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of countries',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: Country::class))),
                        new OA\Property(property: 'pagination', type: 'object', properties: [
                            new OA\Property(property: 'page', type: 'integer'),
                            new OA\Property(property: 'limit', type: 'integer'),
                            new OA\Property(property: 'total', type: 'integer'),
                            new OA\Property(property: 'pages', type: 'integer')
                        ])
                    ]
                )
            )
        ]
    )]
    public function getCountries(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 50)));
        
        $countries = $this->countryRepository->findAllOrderedByName($page, $limit);
        $total = $this->countryRepository->countAll();
        $pages = (int) ceil($total / $limit);
        
        return $this->json([
            'data' => $countries,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages
            ]
        ], Response::HTTP_OK, [], self::SERIALIZATION_GROUPS);
    }

    #[Route('/{uuid}', methods: ['GET'], requirements: ['uuid' => '.+'])]
    #[OA\Get(
        path: '/api/v1/countries/{uuid}',
        summary: 'Get a country by UUID',
        description: 'Retrieve a single country by its UUID',
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                description: 'Country UUID',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Country found',
                content: new OA\JsonContent(ref: new Model(type: Country::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Country not found'
            )
        ]
    )]
    public function getCountry(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);
        if ($country === null) {
            return $this->json(['error' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($country, Response::HTTP_OK, [], self::SERIALIZATION_GROUPS);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/countries',
        summary: 'Create a new country',
        description: 'Create a new country (requires authentication)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Country::class))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Country created',
                content: new OA\JsonContent(ref: new Model(type: Country::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Country with this UUID already exists'
            )
        ],
        security: [['basicAuth' => []]]
    )]
    public function addCountry(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $createRequest = CreateCountryRequest::fromArray($data);
        $errors = $this->validationService->validate($createRequest);

        if (count($errors) > 0) {
            return $this->handleValidationErrors($errors);
        }

        try {
            $country = $this->mapperService->mapCreateRequestToEntity($createRequest);
            $this->entityManager->persist($country);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            $this->logger->warning('Attempted to create duplicate country', [
                'uuid' => $createRequest->uuid,
                'exception' => $e
            ]);
            return $this->json([
                'error' => 'Conflict',
                'message' => sprintf('A country with UUID "%s" already exists', $createRequest->uuid)
            ], Response::HTTP_CONFLICT);
        } catch (DBALException $e) {
            $this->logger->error('Database error while creating country', [
                'uuid' => $createRequest->uuid,
                'exception' => $e
            ]);
            return $this->json([
                'error' => 'Database error',
                'message' => 'An error occurred while creating the country. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error while creating country', [
                'uuid' => $createRequest->uuid,
                'exception' => $e
            ]);
            return $this->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($country, Response::HTTP_CREATED, [], self::SERIALIZATION_GROUPS);
    }

    #[Route('/{uuid}', methods: ['PATCH'], requirements: ['uuid' => '.+'])]
    #[OA\Patch(
        path: '/api/v1/countries/{uuid}',
        summary: 'Update a country',
        description: 'Update an existing country (requires authentication)',
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                description: 'Country UUID',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'region', type: 'string'),
                    new OA\Property(property: 'subRegion', type: 'string'),
                    new OA\Property(property: 'demonym', type: 'string'),
                    new OA\Property(property: 'population', type: 'integer'),
                    new OA\Property(property: 'independent', type: 'boolean'),
                    new OA\Property(property: 'flag', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Country updated',
                content: new OA\JsonContent(ref: new Model(type: Country::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Country not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            )
        ],
        security: [['basicAuth' => []]]
    )]
    public function updateCountry(string $uuid, Request $request): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);
        if ($country === null) {
            return $this->json(['error' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->getJsonData($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $updateRequest = UpdateCountryRequest::fromArray($data);
        $errors = $this->validationService->validate($updateRequest);

        if (count($errors) > 0) {
            return $this->handleValidationErrors($errors);
        }

        try {
            $this->mapperService->mapUpdateRequestToEntity($country, $updateRequest);
            $this->entityManager->flush();
        } catch (DBALException $e) {
            $this->logger->error('Database error while updating country', [
                'uuid' => $uuid,
                'exception' => $e
            ]);
            return $this->json([
                'error' => 'Database error',
                'message' => 'An error occurred while updating the country. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error while updating country', [
                'uuid' => $uuid,
                'exception' => $e
            ]);
            return $this->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($country, Response::HTTP_OK, [], self::SERIALIZATION_GROUPS);
    }

    #[Route('/{uuid}', methods: ['DELETE'], requirements: ['uuid' => '.+'])]
    #[OA\Delete(
        path: '/api/v1/countries/{uuid}',
        summary: 'Delete a country',
        description: 'Delete a country (requires authentication)',
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                description: 'Country UUID',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Country deleted successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Country deleted successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Country not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
            new OA\Response(
                response: 500,
                description: 'Database error'
            )
        ],
        security: [['basicAuth' => []]]
    )]
    public function deleteCountry(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);
        if ($country === null) {
            return $this->json(['error' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $country->softDelete();
            $this->entityManager->flush();
        } catch (DBALException $e) {
            $this->logger->error('Database error while deleting country', [
                'uuid' => $uuid,
                'exception' => $e
            ]);
            return $this->json([
                'error' => 'Database error',
                'message' => 'An error occurred while deleting the country. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error while deleting country', [
                'uuid' => $uuid,
                'exception' => $e
            ]);
            return $this->json([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'message' => sprintf('Country "%s" deleted successfully', $country->getName())
        ], Response::HTTP_OK);
    }

    /**
     * Extract JSON data from request.
     */
    private function getJsonData(Request $request): ?array
    {
        return $this->validationService->getJsonData($request);
    }

    /**
     * Handle validation errors and return formatted response.
     */
    private function handleValidationErrors(ConstraintViolationListInterface $errors): JsonResponse
    {
        return $this->json([
            'errors' => $this->validationService->formatErrors($errors)
        ], Response::HTTP_BAD_REQUEST);
    }

}
