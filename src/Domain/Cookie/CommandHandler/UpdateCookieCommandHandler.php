<?php

declare(strict_types=1);

namespace App\Domain\Cookie\CommandHandler;

use App\Domain\Cookie\CheckCategoryExistsInterface;
use App\Domain\Cookie\CheckNameUniquenessInterface;
use App\Domain\Cookie\Command\UpdateCookieCommand;
use App\Domain\Cookie\CookieRepositoryInterface;
use App\Domain\Cookie\ValueObject\CookieId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class UpdateCookieCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CookieRepositoryInterface $cookieRepository,
        private CheckCategoryExistsInterface $checkCategoryExists,
        private CheckNameUniquenessInterface $checkNameUniqueness,
    ) {}

    public function __invoke(UpdateCookieCommand $command): void
    {
        $cookie = $this->cookieRepository->get(CookieId::fromString($command->cookieId()));

        $cookie->update($command, $this->checkCategoryExists, $this->checkNameUniqueness);

        $this->cookieRepository->save($cookie);
    }
}
