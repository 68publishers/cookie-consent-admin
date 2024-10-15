<?php

declare(strict_types=1);

namespace App\Infrastructure\GlobalSettings\Doctrine;

use App\Domain\GlobalSettings\GlobalSettings;
use App\Domain\GlobalSettings\GlobalSettingsRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Common\Repository\AggregateRootRepositoryInterface;

final readonly class GlobalSettingsRepository implements GlobalSettingsRepositoryInterface
{
    public function __construct(
        private AggregateRootRepositoryInterface $aggregateRootRepository,
        private EntityManagerInterface $em,
    ) {}

    public function save(GlobalSettings $globalSettings): void
    {
        $this->aggregateRootRepository->saveAggregateRoot($globalSettings);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function get(): ?GlobalSettings
    {
        return $this->em->createQueryBuilder()
            ->select('gs')
            ->from(GlobalSettings::class, 'gs')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
