<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie;

use App\Domain\Cookie\CheckCookieProviderExistsInterface;
use App\Domain\CookieProvider\Exception\CookieProviderNotFoundException;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use App\ReadModel\CookieProvider\CookieProviderView;
use App\ReadModel\CookieProvider\GetCookieProviderByIdQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckCookieProviderExists implements CheckCookieProviderExistsInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(CookieProviderId $cookieProviderId): void
    {
        $cookieProvider = $this->queryBus->dispatch(GetCookieProviderByIdQuery::create($cookieProviderId->toString()));

        if (!$cookieProvider instanceof CookieProviderView) {
            throw CookieProviderNotFoundException::withId($cookieProviderId);
        }
    }
}
