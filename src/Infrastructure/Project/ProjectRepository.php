<?php

declare(strict_types=1);

namespace App\Infrastructure\Project;

use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AggregateId;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Common\Repository\AggregateRootRepositoryInterface;

final readonly class ProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private AggregateRootRepositoryInterface $aggregateRootRepository,
    ) {}

    public function save(Project $project): void
    {
        $this->aggregateRootRepository->saveAggregateRoot($project);
    }

    public function get(ProjectId $id): Project
    {
        $project = $this->aggregateRootRepository->loadAggregateRoot(Project::class, AggregateId::fromUuid($id->id()));

        if (!$project instanceof Project) {
            throw ProjectNotFoundException::withId($id);
        }

        return $project;
    }
}
