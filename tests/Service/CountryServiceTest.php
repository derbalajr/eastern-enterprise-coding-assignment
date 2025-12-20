<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Country;
use App\Service\CountryService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

class CountryServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private CountryService $countryService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->countryService = new CountryService($this->httpClient, $this->logger);
    }

    public function testFetchAllCountriesSuccess(): void
    {
        $apiResponse = [
            [
                'cca2' => 'US',
                'cca3' => 'USA',
                'name' => ['common' => 'United States'],
                'region' => 'Americas',
                'subregion' => 'Northern America',
                'demonyms' => ['eng' => ['m' => 'American']],
                'population' => 329484123,
                'independent' => true,
                'flags' => ['svg' => 'https://flagcdn.com/us.svg'],
                'currencies' => [
                    'USD' => ['name' => 'United States dollar', 'symbol' => '$']
                ]
            ],
            [
                'cca2' => 'FR',
                'name' => ['common' => 'France'],
                'region' => 'Europe',
                'flags' => ['png' => 'https://flagcdn.com/fr.png'],
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($apiResponse);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', $this->stringContains('restcountries.com/v3.1/all'))
            ->willReturn($response);

        $this->logger
            ->expects($this->exactly(3))
            ->method('info');

        $countries = $this->countryService->fetchAllCountries();

        $this->assertCount(2, $countries);
        $this->assertInstanceOf(Country::class, $countries[0]);
        $this->assertEquals('US', $countries[0]->getUuid());
        $this->assertEquals('United States', $countries[0]->getName());
        $this->assertEquals('Americas', $countries[0]->getRegion());
        $this->assertNotNull($countries[0]->getCurrency());
        $this->assertEquals('United States dollar', $countries[0]->getCurrency()->getName());
    }

    public function testFetchAllCountriesUsesCca3WhenCca2Missing(): void
    {
        $apiResponse = [
            [
                'cca3' => 'USA',
                'name' => ['common' => 'United States'],
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($apiResponse);

        $this->httpClient->method('request')->willReturn($response);

        $countries = $this->countryService->fetchAllCountries();

        $this->assertEquals('USA', $countries[0]->getUuid());
    }

    public function testFetchAllCountriesThrowsExceptionWhenMissingIdentifiers(): void
    {
        $apiResponse = [
            [
                'name' => ['common' => 'Test Country'],
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($apiResponse);

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Country data missing both cca2 and cca3 identifiers');

        $this->countryService->fetchAllCountries();
    }

    public function testFetchAllCountriesHandlesHttpException(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $exception = $this->createMock(ClientExceptionInterface::class);
        $exception->method('getResponse')->willReturn($response);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch countries from REST Countries API');

        $this->countryService->fetchAllCountries();
    }

    public function testFetchAllCountriesHandlesTransportException(): void
    {
        $exception = $this->createMock(TransportExceptionInterface::class);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch countries from REST Countries API');

        $this->countryService->fetchAllCountries();
    }

    public function testFetchAllCountriesHandlesCountryWithoutCurrency(): void
    {
        $apiResponse = [
            [
                'cca2' => 'US',
                'name' => ['common' => 'United States'],
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($apiResponse);

        $this->httpClient->method('request')->willReturn($response);

        $countries = $this->countryService->fetchAllCountries();

        $this->assertNull($countries[0]->getCurrency());
    }

    public function testFetchAllCountriesUsesFemaleDemonymWhenMaleMissing(): void
    {
        $apiResponse = [
            [
                'cca2' => 'US',
                'name' => ['common' => 'United States'],
                'demonyms' => ['eng' => ['f' => 'American']],
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($apiResponse);

        $this->httpClient->method('request')->willReturn($response);

        $countries = $this->countryService->fetchAllCountries();

        $this->assertEquals('American', $countries[0]->getDemonym());
    }
}

