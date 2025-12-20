<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\CreateCountryRequest;
use App\Service\RequestValidationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidationServiceTest extends TestCase
{
    private ValidatorInterface $validator;
    private RequestValidationService $validationService;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->validationService = new RequestValidationService($this->validator);
    }

    public function testGetJsonDataWithValidJson(): void
    {
        $json = '{"uuid":"US","name":"United States"}';
        $request = Request::create('/', 'POST', [], [], [], [], $json);

        $result = $this->validationService->getJsonData($request);

        $this->assertIsArray($result);
        $this->assertEquals('US', $result['uuid']);
        $this->assertEquals('United States', $result['name']);
    }

    public function testGetJsonDataWithInvalidJson(): void
    {
        $invalidJson = '{invalid json}';
        $request = Request::create('/', 'POST', [], [], [], [], $invalidJson);

        $result = $this->validationService->getJsonData($request);

        $this->assertNull($result);
    }

    public function testGetJsonDataWithEmptyContent(): void
    {
        $request = Request::create('/', 'POST');

        $result = $this->validationService->getJsonData($request);

        $this->assertNull($result);
    }

    public function testValidateDelegatesToValidator(): void
    {
        $object = new CreateCountryRequest();
        $violations = new ConstraintViolationList();

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($object)
            ->willReturn($violations);

        $result = $this->validationService->validate($object);

        $this->assertSame($violations, $result);
    }

    public function testFormatErrorsWithMultipleViolations(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('uuid', 'UUID is required'),
            $this->createViolation('name', 'Name is required'),
            $this->createViolation('population', 'Population must be an integer'),
        ]);

        $result = $this->validationService->formatErrors($violations);

        $this->assertIsArray($result);
        $this->assertEquals('UUID is required', $result['uuid']);
        $this->assertEquals('Name is required', $result['name']);
        $this->assertEquals('Population must be an integer', $result['population']);
    }

    public function testFormatErrorsWithEmptyViolations(): void
    {
        $violations = new ConstraintViolationList();

        $result = $this->validationService->formatErrors($violations);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    private function createViolation(string $propertyPath, string $message): ConstraintViolation
    {
        return new ConstraintViolation(
            $message,
            $message,
            [],
            null,
            $propertyPath,
            null
        );
    }
}

