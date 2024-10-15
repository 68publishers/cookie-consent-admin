<?php

declare(strict_types=1);

namespace App\Domain\Project\CommandHandler;

use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use App\Domain\Project\Command\AddCookieProvidersToProjectCommand;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class AddCookieProvidersToProjectCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
    ) {}

    public function __invoke(AddCookieProvidersToProjectCommand $command): void
    {
        $project = $this->projectRepository->get(ProjectId::fromString($command->projectId()));

        foreach ($command->cookieProviderIds() as $cookieProviderId) {
            $project->addCookieProvider(CookieProviderId::fromString($cookieProviderId));
        }

        $this->projectRepository->save($project);
    }
}
