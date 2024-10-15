<?php

declare(strict_types=1);

namespace App\Domain\User\CommandHandler;

use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\User\Command\AssignProjectsToUserCommand;
use App\Domain\User\User;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;
use SixtyEightPublishers\UserBundle\Domain\Repository\UserRepositoryInterface;
use SixtyEightPublishers\UserBundle\Domain\ValueObject\UserId;

final readonly class AssignProjectsToUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(AssignProjectsToUserCommand $command): void
    {
        $user = $this->userRepository->get(UserId::fromString($command->userId()));
        assert($user instanceof User);

        $user->addProjects(array_map(static fn (string $projectId): ProjectId => ProjectId::fromString($projectId), $command->projectIds()));

        $this->userRepository->save($user);
    }
}
