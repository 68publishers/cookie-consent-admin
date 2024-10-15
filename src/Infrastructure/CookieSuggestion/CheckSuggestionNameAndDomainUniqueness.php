<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieSuggestion;

use App\Domain\CookieSuggestion\CheckSuggestionNameAndDomainUniquenessInterface;
use App\Domain\CookieSuggestion\Exception\NameAndDomainUniquenessException;
use App\Domain\CookieSuggestion\ValueObject\CookieSuggestionId;
use App\Domain\CookieSuggestion\ValueObject\Domain;
use App\Domain\CookieSuggestion\ValueObject\Name;
use App\Domain\Project\ValueObject\ProjectId;
use App\ReadModel\CookieSuggestion\CookieSuggestion;
use App\ReadModel\CookieSuggestion\GetCookieSuggestionByProjectIdAndNameAndDomainQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckSuggestionNameAndDomainUniqueness implements CheckSuggestionNameAndDomainUniquenessInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(CookieSuggestionId $cookieSuggestionId, ProjectId $projectId, Name $name, Domain $domain): void
    {
        $cookieSuggestionView = $this->queryBus->dispatch(GetCookieSuggestionByProjectIdAndNameAndDomainQuery::create(
            $projectId->toString(),
            $name->value(),
            $domain->value(),
        ));

        if ($cookieSuggestionView instanceof CookieSuggestion && !$cookieSuggestionId->equals(CookieSuggestionId::fromString($cookieSuggestionView->id))) {
            throw NameAndDomainUniquenessException::create($name->value(), $domain->value(), $projectId->toString());
        }
    }
}
