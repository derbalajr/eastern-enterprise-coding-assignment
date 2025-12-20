<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CreateCountryRequest;
use App\DTO\UpdateCountryRequest;
use App\Entity\Country;
use App\Entity\Currency;

class CountryMapperService
{
    public function mapCreateRequestToEntity(CreateCountryRequest $request): Country
    {
        $country = new Country();
        $country->setUuid($request->uuid ?? uniqid('country_', true));
        $country->setName($request->name ?? '');
        $country->setRegion($request->region);
        $country->setSubRegion($request->subRegion);
        $country->setDemonym($request->demonym);
        $country->setPopulation($request->population);
        $country->setIndependent($request->independent);
        $country->setFlag($request->flag);

        if ($request->currency !== null) {
            $currency = new Currency();
            $currency->setName($request->currency->name);
            $currency->setSymbol($request->currency->symbol);
            $country->setCurrency($currency);
        }

        return $country;
    }

    public function mapUpdateRequestToEntity(Country $country, UpdateCountryRequest $request): void
    {
        if ($request->name !== null) $country->setName($request->name);
        if ($request->region !== null) $country->setRegion($request->region);
        if ($request->subRegion !== null) $country->setSubRegion($request->subRegion);
        if ($request->demonym !== null) $country->setDemonym($request->demonym);
        if ($request->population !== null) $country->setPopulation($request->population);
        if ($request->independent !== null) $country->setIndependent($request->independent);
        if ($request->flag !== null) $country->setFlag($request->flag);

        if ($request->currency !== null) {
            $currency = $country->getCurrency() ?? new Currency();
            if ($request->currency->name !== null) $currency->setName($request->currency->name);
            if ($request->currency->symbol !== null) $currency->setSymbol($request->currency->symbol);
            $country->setCurrency($currency);
        }
    }
}

