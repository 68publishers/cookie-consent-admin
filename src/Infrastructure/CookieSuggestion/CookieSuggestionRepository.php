<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieSuggestion;

use App\Domain\CookieSuggestion\CookieSuggestion;
use App\Domain\CookieSuggestion\CookieSuggestionRepositoryInterface;
use App\Domain\CookieSuggestion\Exception\CookieSuggestionNotFoundException;
use App\Domain\CookieSuggestion\ValueObject\CookieSuggestionId;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AggregateId;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Common\Repository\AggregateRootRepositoryInterface;

final readonly class CookieSuggestionRepository implements CookieSuggestionRepositoryInterface
{
    public function __construct(
        private AggregateRootRepositoryInterface $aggregateRootRepository,
    ) {}

    public function save(CookieSuggestion $cookieSuggestion): void
    {
        $this->aggregateRootRepository->saveAggregateRoot($cookieSuggestion);
    }

    public function get(CookieSuggestionId $id): CookieSuggestion
    {
        $cookieSuggestion = $this->aggregateRootRepository->loadAggregateRoot(CookieSuggestion::class, AggregateId::fromUuid($id->id()));

        if (!$cookieSuggestion instanceof CookieSuggestion) {
            throw CookieSuggestionNotFoundException::byId($id);
        }

        return $cookieSuggestion;
    }
}
