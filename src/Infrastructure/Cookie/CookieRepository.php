<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie;

use App\Domain\Cookie\Cookie;
use App\Domain\Cookie\CookieRepositoryInterface;
use App\Domain\Cookie\Exception\CookieNotFoundException;
use App\Domain\Cookie\ValueObject\CookieId;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AggregateId;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Common\Repository\AggregateRootRepositoryInterface;

final readonly class CookieRepository implements CookieRepositoryInterface
{
    public function __construct(
        private AggregateRootRepositoryInterface $aggregateRootRepository,
    ) {}

    public function save(Cookie $cookie): void
    {
        $this->aggregateRootRepository->saveAggregateRoot($cookie);
    }

    public function get(CookieId $id): Cookie
    {
        $cookie = $this->aggregateRootRepository->loadAggregateRoot(Cookie::class, AggregateId::fromUuid($id->id()));

        if (!$cookie instanceof Cookie) {
            throw CookieNotFoundException::withId($id);
        }

        return $cookie;
    }
}
