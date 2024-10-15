<?php

declare(strict_types=1);

namespace App\Domain\User\CommandHandler;

use App\Domain\User\Command\StoreExternalAuthenticationCommand;
use App\Domain\User\User;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;
use SixtyEightPublishers\UserBundle\Domain\Repository\UserRepositoryInterface;
use SixtyEightPublishers\UserBundle\Domain\ValueObject\UserId;

final readonly class StoreExternalAuthenticationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(StoreExternalAuthenticationCommand $command): void
    {
        $user = $this->userRepository->get(UserId::fromString($command->userId()));
        assert($user instanceof User);

        $user->storeExternalAuthentication($command);

        $this->userRepository->save($user);
    }
}
