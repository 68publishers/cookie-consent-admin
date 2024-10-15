<?php

declare(strict_types=1);

namespace App\Domain\Cookie\CommandHandler;

use App\Domain\Cookie\Command\DeleteCookieCommand;
use App\Domain\Cookie\CookieRepositoryInterface;
use App\Domain\Cookie\ValueObject\CookieId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class DeleteCookieCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CookieRepositoryInterface $cookieRepository,
    ) {}

    public function __invoke(DeleteCookieCommand $command): void
    {
        $cookie = $this->cookieRepository->get(CookieId::fromString($command->cookieId()));

        $cookie->delete();

        $this->cookieRepository->save($cookie);
    }
}
