<?php

declare(strict_types=1);

namespace App\Infrastructure\ConsentSettings;

use App\Domain\ConsentSettings\ConsentSettings;
use App\Domain\ConsentSettings\ConsentSettingsRepositoryInterface;
use App\Domain\ConsentSettings\Exception\ConsentSettingsNotFoundException;
use App\Domain\ConsentSettings\ValueObject\ConsentSettingsId;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AggregateId;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Common\Repository\AggregateRootRepositoryInterface;

final readonly class ConsentSettingsRepository implements ConsentSettingsRepositoryInterface
{
    public function __construct(
        private AggregateRootRepositoryInterface $aggregateRootRepository,
    ) {}

    public function save(ConsentSettings $consentSettings): void
    {
        $this->aggregateRootRepository->saveAggregateRoot($consentSettings);
    }

    public function get(ConsentSettingsId $id): ConsentSettings
    {
        $consentSettings = $this->aggregateRootRepository->loadAggregateRoot(ConsentSettings::class, AggregateId::fromUuid($id->id()));

        if (!$consentSettings instanceof ConsentSettings) {
            throw ConsentSettingsNotFoundException::withId($id);
        }

        return $consentSettings;
    }
}
