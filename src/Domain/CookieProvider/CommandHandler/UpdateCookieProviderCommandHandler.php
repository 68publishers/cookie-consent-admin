<?php

declare(strict_types=1);

namespace App\Domain\CookieProvider\CommandHandler;

use App\Domain\CookieProvider\CheckCodeUniquenessInterface;
use App\Domain\CookieProvider\Command\UpdateCookieProviderCommand;
use App\Domain\CookieProvider\CookieProviderRepositoryInterface;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class UpdateCookieProviderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CookieProviderRepositoryInterface $cookieProviderRepository,
        private CheckCodeUniquenessInterface $checkCodeUniqueness,
    ) {}

    public function __invoke(UpdateCookieProviderCommand $command): void
    {
        $category = $this->cookieProviderRepository->get(CookieProviderId::fromString($command->cookieProviderId()));

        $category->update($command, $this->checkCodeUniqueness);

        $this->cookieProviderRepository->save($category);
    }
}
