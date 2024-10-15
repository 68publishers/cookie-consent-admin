<?php

declare(strict_types=1);

namespace App\Infrastructure\Import;

use App\Domain\Import\Exception\ImportNotFoundException;
use App\Domain\Import\Import;
use App\Domain\Import\ImportRepositoryInterface;
use App\Domain\Import\ValueObject\ImportId;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AggregateId;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Common\Repository\AggregateRootRepositoryInterface;

final readonly class ImportRepository implements ImportRepositoryInterface
{
    public function __construct(
        private AggregateRootRepositoryInterface $aggregateRootRepository,
    ) {}

    public function save(Import $import): void
    {
        $this->aggregateRootRepository->saveAggregateRoot($import);
    }

    public function get(ImportId $id): Import
    {
        $import = $this->aggregateRootRepository->loadAggregateRoot(Import::class, AggregateId::fromUuid($id->id()));

        if (!$import instanceof Import) {
            throw ImportNotFoundException::withId($id);
        }

        return $import;
    }
}
