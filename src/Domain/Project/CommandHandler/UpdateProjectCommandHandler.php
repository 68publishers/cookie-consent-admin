<?php

declare(strict_types=1);

namespace App\Domain\Project\CommandHandler;

use App\Domain\Project\CheckCodeUniquenessInterface;
use App\Domain\Project\Command\UpdateProjectCommand;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class UpdateProjectCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CheckCodeUniquenessInterface $checkCodeUniqueness,
    ) {}

    public function __invoke(UpdateProjectCommand $command): void
    {
        $project = $this->projectRepository->get(ProjectId::fromString($command->projectId()));

        $project->update($command, $this->checkCodeUniqueness);

        $this->projectRepository->save($project);
    }
}
