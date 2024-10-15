<?php

declare(strict_types=1);

namespace App\Domain\Cookie\CommandHandler;

use App\Domain\Cookie\CheckCategoryExistsInterface;
use App\Domain\Cookie\CheckCookieProviderExistsInterface;
use App\Domain\Cookie\CheckNameUniquenessInterface;
use App\Domain\Cookie\Command\CreateCookieCommand;
use App\Domain\Cookie\Cookie;
use App\Domain\Cookie\CookieRepositoryInterface;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class CreateCookieCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CookieRepositoryInterface $cookieRepository,
        private CheckCategoryExistsInterface $checkCategoryExists,
        private CheckCookieProviderExistsInterface $checkCookieProviderExists,
        private CheckNameUniquenessInterface $checkNameUniqueness,
    ) {}

    public function __invoke(CreateCookieCommand $command): void
    {
        $cookie = Cookie::create($command, $this->checkCategoryExists, $this->checkCookieProviderExists, $this->checkNameUniqueness);

        $this->cookieRepository->save($cookie);
    }
}
