<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Service\CountryService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CountrySyncCommand extends Command
{
    private const LOG_PREFIX = '[CountrySync]';

    public function __construct(
        private readonly CountryService $countryService,
        private readonly CountryRepository $countryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('countries:sync')
            ->setDescription('Synchronize countries from REST Countries API');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Synchronizing countries from REST Countries API');

        $this->logger->info(self::LOG_PREFIX . ' Starting country synchronization');

        try {
            $io->info('Fetching countries from API...');
            $this->logger->info(self::LOG_PREFIX . ' Fetching countries from REST Countries API');
            $countries = $this->countryService->fetchAllCountries();
            $countryCount = count($countries);
            $this->logger->info(self::LOG_PREFIX . " Fetched {$countryCount} countries from API");
            $io->success(sprintf('Fetched %d countries from API', $countryCount));

            $io->info('Syncing countries to database...');
            $this->logger->info(self::LOG_PREFIX . ' Syncing countries to database');
            $stats = $this->syncCountries($countries);
            $removed = $this->removeObsoleteCountries($countries);

            $this->entityManager->flush();
            $this->logger->info(self::LOG_PREFIX . ' Database flush completed');

            $restored = $stats['restored'] ?? 0;
            $this->logger->info(self::LOG_PREFIX . " Synchronization completed: created={$stats['created']}, updated={$stats['updated']}, restored={$restored}, removed={$removed}");

            $io->success([
                sprintf('Created: %d countries', $stats['created']),
                sprintf('Updated: %d countries', $stats['updated']),
                sprintf('Restored: %d countries', $restored),
                sprintf('Removed: %d countries', $removed),
                'Synchronization completed successfully!'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX . ' Failed to synchronize countries: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            $io->error('Failed to synchronize countries: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function syncCountries(array $countries): array
    {
        $created = 0;
        $updated = 0;
        $restored = 0;

        foreach ($countries as $country) {
            $existing = $this->countryRepository->findByUuidIncludingDeleted($country->getUuid());

            if ($existing === null) {
                $this->entityManager->persist($country);
                $this->logger->debug(self::LOG_PREFIX . " Creating new country: {$country->getUuid()} - {$country->getName()}");
                $created++;
            } else {
                if ($existing->isDeleted()) {
                    $existing->restore();
                    $this->logger->debug(self::LOG_PREFIX . " Restoring soft-deleted country: {$country->getUuid()} - {$country->getName()}");
                    $restored++;
                }
                $this->updateCountryFromApi($existing, $country);
                $this->logger->debug(self::LOG_PREFIX . " Updating country: {$country->getUuid()} - {$country->getName()}");
                $updated++;
            }
        }

        $this->logger->info(self::LOG_PREFIX . " Sync completed: created={$created}, updated={$updated}, restored={$restored}");

        return ['created' => $created, 'updated' => $updated, 'restored' => $restored];
    }

    private function removeObsoleteCountries(array $countries): int
    {
        $apiUuids = array_map(fn(Country $country) => $country->getUuid(), $countries);
        $this->logger->info(self::LOG_PREFIX . ' Removing countries that no longer exist in API');
        $removed = $this->countryRepository->removeCountriesNotInList($apiUuids);
        $this->logger->info(self::LOG_PREFIX . " Removed {$removed} obsolete countries");
        return $removed;
    }

    private function updateCountryFromApi(Country $existing, Country $fromApi): void
    {
        $existing->setName($fromApi->getName());
        $existing->setRegion($fromApi->getRegion());
        $existing->setSubRegion($fromApi->getSubRegion());
        $existing->setDemonym($fromApi->getDemonym());
        $existing->setPopulation($fromApi->getPopulation());
        $existing->setIndependent($fromApi->isIndependent());
        $existing->setFlag($fromApi->getFlag());
        $existing->setCurrency($fromApi->getCurrency());
    }
}
