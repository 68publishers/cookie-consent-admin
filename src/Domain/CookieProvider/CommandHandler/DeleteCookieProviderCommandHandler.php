<?php

declare(strict_types=1);

namespace App\Domain\CookieProvider\CommandHandler;

use App\Domain\CookieProvider\Command\DeleteCookieProviderCommand;
use App\Domain\CookieProvider\CookieProviderRepositoryInterface;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class DeleteCookieProviderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CookieProviderRepositoryInterface $cookieProviderRepository,
    ) {}

    public function __invoke(DeleteCookieProviderCommand $command): void
    {
        $cookieProvider = $this->cookieProviderRepository->get(CookieProviderId::fromString($command->cookieProviderId()));

        $cookieProvider->delete();

        $this->cookieProviderRepository->save($cookieProvider);
    }
}
