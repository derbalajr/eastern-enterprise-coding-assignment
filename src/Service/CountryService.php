<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Country;
use App\Entity\Currency;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Service for fetching country data from REST Countries API.
 */
class CountryService
{
    private const REST_COUNTRIES_API_URL = 'https://restcountries.com/v3.1/all';
    private const REQUIRED_FIELDS = 'cca2,cca3,name,region,subregion,demonyms,population,independent,flags,currencies';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * Fetches all countries from REST Countries API and returns them as Country entities.
     *
     * @return Country[]
     * @throws \RuntimeException When API request fails
     */
    public function fetchAllCountries(): array
    {
        try {
            $url = self::REST_COUNTRIES_API_URL . '?fields=' . self::REQUIRED_FIELDS;
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            return array_map(
                fn(array $countryData): Country => $this->transformToCountry($countryData),
                $data
            );
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new \RuntimeException(
                'Failed to fetch countries from REST Countries API: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Transforms REST Countries API data to Country entity.
     *
     * @param array<string, mixed> $data API response data for a single country
     * @return Country
     * @throws \RuntimeException When required country identifier is missing
     */
    private function transformToCountry(array $data): Country
    {
        $country = new Country();
        
        $uuid = $data['cca2'] ?? $data['cca3'] ?? null;
        if ($uuid === null) {
            throw new \RuntimeException('Country data missing both cca2 and cca3 identifiers');
        }
        
        $country->setUuid($uuid);
        $country->setName($data['name']['common'] ?? '');
        $country->setRegion($data['region'] ?? null);
        $country->setSubRegion($data['subregion'] ?? null);
        $country->setDemonym($data['demonyms']['eng']['m'] ?? $data['demonyms']['eng']['f'] ?? null);
        $country->setPopulation($data['population'] ?? null);
        $country->setIndependent($data['independent'] ?? null);
        $country->setFlag($data['flags']['svg'] ?? $data['flags']['png'] ?? null);
        
        if (!empty($data['currencies'])) {
            $currencyData = reset($data['currencies']);
            $currency = new Currency();
            $currency->setName($currencyData['name'] ?? null);
            $currency->setSymbol($currencyData['symbol'] ?? null);
            $country->setCurrency($currency);
        }
        
        return $country;
    }
}
