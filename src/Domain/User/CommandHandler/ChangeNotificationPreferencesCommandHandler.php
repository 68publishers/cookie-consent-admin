<?php

declare(strict_types=1);

namespace App\Domain\User\CommandHandler;

use App\Domain\User\Command\ChangeNotificationPreferencesCommand;
use App\Domain\User\User;
use App\Domain\User\ValueObject\NotificationPreferences;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;
use SixtyEightPublishers\UserBundle\Domain\Repository\UserRepositoryInterface;
use SixtyEightPublishers\UserBundle\Domain\ValueObject\UserId;

final readonly class ChangeNotificationPreferencesCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(ChangeNotificationPreferencesCommand $command): void
    {
        $user = $this->userRepository->get(UserId::fromString($command->userId()));
        assert($user instanceof User);

        $user->changeNotificationPreferences(NotificationPreferences::reconstitute($command->notificationTypes()));

        $this->userRepository->save($user);
    }
}
