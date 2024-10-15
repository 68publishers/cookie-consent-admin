<?php

declare(strict_types=1);

namespace App\Domain\Project\CommandHandler;

use App\Domain\Project\CheckCodeUniquenessInterface;
use App\Domain\Project\Command\CreateProjectCommand;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepositoryInterface;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class CreateProjectCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CheckCodeUniquenessInterface $checkCodeUniqueness,
    ) {}

    public function __invoke(CreateProjectCommand $command): void
    {
        $project = Project::create($command, $this->checkCodeUniqueness);

        $this->projectRepository->save($project);
    }
}
