<?php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\CreateCountryRequest;
use App\DTO\CurrencyRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class CreateCountryRequestTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testFromArrayWithAllFields(): void
    {
        $data = [
            'uuid' => 'US',
            'name' => 'United States',
            'region' => 'Americas',
            'subRegion' => 'Northern America',
            'demonym' => 'American',
            'population' => 329484123,
            'independent' => true,
            'flag' => 'https://flagcdn.com/us.svg',
            'currency' => [
                'name' => 'United States dollar',
                'symbol' => '$'
            ]
        ];

        $request = CreateCountryRequest::fromArray($data);

        $this->assertEquals('US', $request->uuid);
        $this->assertEquals('United States', $request->name);
        $this->assertEquals('Americas', $request->region);
        $this->assertEquals('Northern America', $request->subRegion);
        $this->assertEquals('American', $request->demonym);
        $this->assertEquals(329484123, $request->population);
        $this->assertTrue($request->independent);
        $this->assertEquals('https://flagcdn.com/us.svg', $request->flag);
        $this->assertInstanceOf(CurrencyRequest::class, $request->currency);
    }

    public function testFromArrayWithMinimalFields(): void
    {
        $data = [
            'uuid' => 'FR',
            'name' => 'France'
        ];

        $request = CreateCountryRequest::fromArray($data);

        $this->assertEquals('FR', $request->uuid);
        $this->assertEquals('France', $request->name);
        $this->assertNull($request->currency);
    }

    public function testValidationRequiresUuid(): void
    {
        $request = new CreateCountryRequest();
        $request->name = 'Test';

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('UUID is required', (string) $violations);
    }

    public function testValidationRequiresName(): void
    {
        $request = new CreateCountryRequest();
        $request->uuid = 'US';

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('Name is required', (string) $violations);
    }

    public function testValidationRejectsUuidTooLong(): void
    {
        $request = new CreateCountryRequest();
        $request->uuid = str_repeat('A', 37);
        $request->name = 'Test';

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('UUID cannot be longer than 36 characters', (string) $violations);
    }

    public function testValidationRejectsNegativePopulation(): void
    {
        $request = new CreateCountryRequest();
        $request->uuid = 'US';
        $request->name = 'Test';
        $request->population = -100;

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('Population must be positive or zero', (string) $violations);
    }

    public function testValidationRejectsInvalidFlagUrl(): void
    {
        $request = new CreateCountryRequest();
        $request->uuid = 'US';
        $request->name = 'Test';
        $request->flag = 'not-a-valid-url';

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('Flag must be a valid URL', (string) $violations);
    }

    public function testValidationAcceptsValidData(): void
    {
        $request = new CreateCountryRequest();
        $request->uuid = 'US';
        $request->name = 'United States';
        $request->population = 329484123;
        $request->independent = true;
        $request->flag = 'https://flagcdn.com/us.svg';

        $violations = $this->validator->validate($request);

        $this->assertEquals(0, $violations->count());
    }
}

