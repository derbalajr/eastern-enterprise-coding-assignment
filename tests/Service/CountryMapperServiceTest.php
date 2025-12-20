<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\CreateCountryRequest;
use App\DTO\CurrencyRequest;
use App\DTO\UpdateCountryRequest;
use App\Entity\Country;
use App\Entity\Currency;
use App\Service\CountryMapperService;
use PHPUnit\Framework\TestCase;

class CountryMapperServiceTest extends TestCase
{
    private CountryMapperService $mapperService;

    protected function setUp(): void
    {
        $this->mapperService = new CountryMapperService();
    }

    public function testMapCreateRequestToEntityWithAllFields(): void
    {
        $request = new CreateCountryRequest();
        $request->uuid = 'US';
        $request->name = 'United States';
        $request->region = 'Americas';
        $request->subRegion = 'Northern America';
        $request->demonym = 'American';
        $request->population = 329484123;
        $request->independent = true;
        $request->flag = 'https://flagcdn.com/us.svg';
        
        $currencyRequest = new CurrencyRequest();
        $currencyRequest->name = 'United States dollar';
        $currencyRequest->symbol = '$';
        $request->currency = $currencyRequest;

        $country = $this->mapperService->mapCreateRequestToEntity($request);

        $this->assertInstanceOf(Country::class, $country);
        $this->assertEquals('US', $country->getUuid());
        $this->assertEquals('United States', $country->getName());
        $this->assertEquals('Americas', $country->getRegion());
        $this->assertEquals('Northern America', $request->subRegion);
        $this->assertEquals('American', $country->getDemonym());
        $this->assertEquals(329484123, $country->getPopulation());
        $this->assertTrue($country->isIndependent());
        $this->assertEquals('https://flagcdn.com/us.svg', $country->getFlag());
        
        $currency = $country->getCurrency();
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertEquals('United States dollar', $currency->getName());
        $this->assertEquals('$', $currency->getSymbol());
    }

    public function testMapCreateRequestToEntityWithMinimalFields(): void
    {
        $request = new CreateCountryRequest();
        $request->uuid = 'FR';
        $request->name = 'France';

        $country = $this->mapperService->mapCreateRequestToEntity($request);

        $this->assertEquals('FR', $country->getUuid());
        $this->assertEquals('France', $country->getName());
        $this->assertNull($country->getRegion());
        $this->assertNull($country->getCurrency());
    }

    public function testMapCreateRequestToEntityGeneratesUuidWhenMissing(): void
    {
        $request = new CreateCountryRequest();
        $request->name = 'Test Country';

        $country = $this->mapperService->mapCreateRequestToEntity($request);

        $this->assertNotEmpty($country->getUuid());
        $this->assertStringStartsWith('country_', $country->getUuid());
    }

    public function testMapUpdateRequestToEntityUpdatesOnlyProvidedFields(): void
    {
        $country = new Country();
        $country->setUuid('US');
        $country->setName('United States');
        $country->setRegion('Americas');
        $country->setPopulation(300000000);

        $updateRequest = new UpdateCountryRequest();
        $updateRequest->name = 'United States of America';
        $updateRequest->population = 329484123;

        $this->mapperService->mapUpdateRequestToEntity($country, $updateRequest);

        $this->assertEquals('United States of America', $country->getName());
        $this->assertEquals(329484123, $country->getPopulation());
        $this->assertEquals('Americas', $country->getRegion()); // Should remain unchanged
    }

    public function testMapUpdateRequestToEntityWithCurrency(): void
    {
        $country = new Country();
        $country->setUuid('US');

        $updateRequest = new UpdateCountryRequest();
        $currencyRequest = new CurrencyRequest();
        $currencyRequest->name = 'US Dollar';
        $currencyRequest->symbol = '$';
        $updateRequest->currency = $currencyRequest;

        $this->mapperService->mapUpdateRequestToEntity($country, $updateRequest);

        $currency = $country->getCurrency();
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertEquals('US Dollar', $currency->getName());
        $this->assertEquals('$', $currency->getSymbol());
    }

    public function testMapUpdateRequestToEntityUpdatesExistingCurrency(): void
    {
        $country = new Country();
        $existingCurrency = new Currency();
        $existingCurrency->setName('Old Currency');
        $existingCurrency->setSymbol('OC');
        $country->setCurrency($existingCurrency);

        $updateRequest = new UpdateCountryRequest();
        $currencyRequest = new CurrencyRequest();
        $currencyRequest->name = 'New Currency';
        $currencyRequest->symbol = 'NC';
        $updateRequest->currency = $currencyRequest;

        $this->mapperService->mapUpdateRequestToEntity($country, $updateRequest);

        $currency = $country->getCurrency();
        $this->assertSame($existingCurrency, $currency); // Should reuse existing currency object
        $this->assertEquals('New Currency', $currency->getName());
        $this->assertEquals('NC', $currency->getSymbol());
    }

    public function testMapUpdateRequestToEntityWithPartialCurrencyUpdate(): void
    {
        $country = new Country();
        $existingCurrency = new Currency();
        $existingCurrency->setName('US Dollar');
        $existingCurrency->setSymbol('$');
        $country->setCurrency($existingCurrency);

        $updateRequest = new UpdateCountryRequest();
        $currencyRequest = new CurrencyRequest();
        $currencyRequest->symbol = 'USD';
        $updateRequest->currency = $currencyRequest;

        $this->mapperService->mapUpdateRequestToEntity($country, $updateRequest);

        $currency = $country->getCurrency();
        $this->assertEquals('US Dollar', $currency->getName()); // Should remain unchanged
        $this->assertEquals('USD', $currency->getSymbol()); // Should be updated
    }
}

