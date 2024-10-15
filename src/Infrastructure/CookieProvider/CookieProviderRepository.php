<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieProvider;

use App\Domain\CookieProvider\CookieProvider;
use App\Domain\CookieProvider\CookieProviderRepositoryInterface;
use App\Domain\CookieProvider\Exception\CookieProviderNotFoundException;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AggregateId;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Common\Repository\AggregateRootRepositoryInterface;

final readonly class CookieProviderRepository implements CookieProviderRepositoryInterface
{
    public function __construct(
        private AggregateRootRepositoryInterface $aggregateRootRepository,
    ) {}

    public function save(CookieProvider $cookieProvider): void
    {
        $this->aggregateRootRepository->saveAggregateRoot($cookieProvider);
    }

    public function get(CookieProviderId $id): CookieProvider
    {
        $cookieProvider = $this->aggregateRootRepository->loadAggregateRoot(CookieProvider::class, AggregateId::fromUuid($id->id()));

        if (!$cookieProvider instanceof CookieProvider) {
            throw CookieProviderNotFoundException::withId($id);
        }

        return $cookieProvider;
    }
}
