<?php
declare(strict_types=1);

namespace App\Controller\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('countries')]
class CountryController extends AbstractController
{
    #[Route('/{country}', methods: ['GET'])]
    public function getCountry(): void
    {
        // TODO
    }

    #[Route('/list', methods: ['GET'])]
    public function getCountries(): void
    {
        // TODO
    }

    #[Route('/', methods: ['POST'])]
    public function addCountry(): void
    {
        // TODO
    }

    #[Route('/{country}', methods: ['PATCH'])]
    public function updateCountry(): void
    {
        // TODO
    }

    #[Route('/{country}', methods: ['DELETE'])]
    public function deleteCountry(): void
    {
        // TODO
    }
}