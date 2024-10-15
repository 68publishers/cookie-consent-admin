<?php

declare(strict_types=1);

namespace App\Subscribers\Shared;

use App\Application\Project\Command\RecalculateCookieSuggestionStatisticsCommand;
use App\Domain\Category\Category;
use App\Domain\Category\Event\CategoryCodeChanged;
use App\Domain\Cookie\Cookie;
use App\Domain\Cookie\Event\CookieCategoryChanged;
use App\Domain\Cookie\Event\CookieCreated;
use App\Domain\Cookie\Event\CookieDomainChanged;
use App\Domain\Cookie\Event\CookieNameChanged;
use App\Domain\CookieProvider\CookieProvider;
use App\Domain\CookieSuggestion\Event\CookieOccurrenceAdded;
use App\Domain\CookieSuggestion\Event\CookieOccurrenceLastFoundAtChanged;
use App\Domain\CookieSuggestion\Event\CookieSuggestionCreated;
use App\Domain\CookieSuggestion\Event\CookieSuggestionIgnoredPermanentlyChanged;
use App\Domain\CookieSuggestion\Event\CookieSuggestionIgnoredUntilNextOccurrenceChanged;
use App\Domain\Project\Event\ProjectCookieProviderAdded;
use App\Domain\Project\Event\ProjectCookieProviderRemoved;
use App\Domain\Project\Event\ProjectCreated;
use App\Domain\Project\Event\ProjectDomainChanged;
use App\ReadModel\Cookie\GetCookieProviderIdByCookieIdQuery;
use App\ReadModel\CookieSuggestion\CookieSuggestion;
use App\ReadModel\CookieSuggestion\GetCookieSuggestionByIdQuery;
use App\ReadModel\Project\FindAllProjectIdsByCookieProviderIdQuery;
use App\ReadModel\Project\FindAllProjectIdsQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Domain\Event\AggregateDeleted;
use SixtyEightPublishers\ArchitectureBundle\Event\EventHandlerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class RecalculateCookieSuggestionsStatisticsWhenAnythingRelatedChanged implements EventHandlerInterface, MessageSubscriberInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public static function getHandledMessages(): iterable
    {
        # project
        yield ProjectCreated::class => [
            'method' => 'whenProjectCreated',
        ];
        yield ProjectDomainChanged::class => [
            'method' => 'whenProjectDomainChanged',
        ];
        yield ProjectCookieProviderAdded::class => [
            'method' => 'whenProjectCookieProviderAdded',
        ];
        yield ProjectCookieProviderRemoved::class => [
            'method' => 'whenProjectCookieProviderRemoved',
        ];

        # category
        yield CategoryCodeChanged::class => [
            'method' => 'whenCategoryCodeChanged',
        ];

        # cookie
        yield CookieCreated::class => [
            'method' => 'whenCookieCreated',
        ];
        yield CookieCategoryChanged::class => [
            'method' => 'whenCookieCategoryChanged',
        ];
        yield CookieDomainChanged::class => [
            'method' => 'whenCookieDomainChanged',
        ];
        yield CookieNameChanged::class => [
            'method' => 'whenCookieNameChanged',
        ];

        # cookie suggestion
        yield CookieSuggestionCreated::class => [
            'method' => 'whenCookieSuggestionCreated',
        ];
        yield CookieSuggestionIgnoredPermanentlyChanged::class => [
            'method' => 'whenCookieSuggestionIgnoredPermanentlyChanged',
        ];
        yield CookieSuggestionIgnoredUntilNextOccurrenceChanged::class => [
            'method' => 'whenCookieSuggestionIgnoredUntilNextOccurrenceChanged',
        ];

        # cookie suggestion - occurrences
        yield CookieOccurrenceAdded::class => [
            'method' => 'whenCookieOccurrenceAdded',
        ];
        yield CookieOccurrenceLastFoundAtChanged::class => [
            'method' => 'whenCookieOccurrenceLastFoundAtChanged',
        ];

        # deletes
        yield AggregateDeleted::class => [
            'method' => 'whenAggregateDeleted',
        ];
    }

    public function whenProjectDomainChanged(ProjectDomainChanged $event): void
    {
        $this->doDispatch([$event->projectId()->toString()]);
    }

    public function whenProjectCreated(ProjectCreated $event): void
    {
        $this->doDispatch([$event->projectId()->toString()]);
    }

    public function whenProjectCookieProviderAdded(ProjectCookieProviderAdded $event): void
    {
        $this->doDispatch([$event->projectId()->toString()]);
    }

    public function whenProjectCookieProviderRemoved(ProjectCookieProviderRemoved $event): void
    {
        $this->doDispatch([$event->projectId()->toString()]);
    }

    public function whenCategoryCodeChanged(): void
    {
        $this->doDispatch($this->getAllProjectIds());
    }

    public function whenCookieCreated(CookieCreated $event): void
    {
        $this->doDispatch($this->getAllProjectIdsByCookieProviderId($event->cookieProviderId()->toString()));
    }

    public function whenCookieCategoryChanged(CookieCategoryChanged $event): void
    {
        $this->doDispatch($this->getAllProjectIdsByCookieId($event->cookieId()->toString()));
    }

    public function whenCookieDomainChanged(CookieDomainChanged $event): void
    {
        $this->doDispatch($this->getAllProjectIdsByCookieId($event->cookieId()->toString()));
    }

    public function whenCookieNameChanged(CookieNameChanged $event): void
    {
        $this->doDispatch($this->getAllProjectIdsByCookieId($event->cookieId()->toString()));
    }

    public function whenCookieSuggestionCreated(CookieSuggestionCreated $event): void
    {
        $this->doDispatch([$event->projectId()->toString()]);
    }

    public function whenCookieSuggestionIgnoredPermanentlyChanged(CookieSuggestionIgnoredPermanentlyChanged $event): void
    {
        $this->doDispatch($this->getAllProjectIdsByCookieSuggestionId($event->cookieSuggestionId()->toString()));
    }

    public function whenCookieSuggestionIgnoredUntilNextOccurrenceChanged(CookieSuggestionIgnoredUntilNextOccurrenceChanged $event): void
    {
        $this->doDispatch($this->getAllProjectIdsByCookieSuggestionId($event->cookieSuggestionId()->toString()));
    }

    public function whenCookieOccurrenceAdded(CookieOccurrenceAdded $event): void
    {
        $this->doDispatch($this->getAllProjectIdsByCookieSuggestionId($event->cookieSuggestionId()->toString()));
    }

    public function whenCookieOccurrenceLastFoundAtChanged(CookieOccurrenceLastFoundAtChanged $event): void
    {
        $this->doDispatch($this->getAllProjectIdsByCookieSuggestionId($event->cookieSuggestionId()->toString()));
    }

    public function whenAggregateDeleted(AggregateDeleted $event): void
    {
        $ids = [];

        switch ($event->aggregateClassname()) {
            case Category::class:
                $ids = $this->getAllProjectIds();

                break;
                #case CookieProvider::class: # we don't need to trigger it when a provider is deleted because all cookies are deleted also
            case Cookie::class:
                $ids = $this->getAllProjectIdsByCookieId($event->aggregateId()->toString());
        }

        $this->doDispatch($ids);
    }

    /**
     * @param non-empty-list<string> $projectIds
     */
    private function doDispatch(array $projectIds): void
    {
        if (empty($projectIds)) {
            return;
        }

        $this->commandBus->dispatch(RecalculateCookieSuggestionStatisticsCommand::create($projectIds), [
            new DispatchAfterCurrentBusStamp(),
        ]);
    }

    /**
     * @return array<string>
     */
    private function getAllProjectIds(): array
    {
        return $this->queryBus->dispatch(FindAllProjectIdsQuery::create());
    }

    /**
     * @return array<string>
     */
    private function getAllProjectIdsByCookieProviderId(string $cookieProviderId): array
    {
        return $this->queryBus->dispatch(FindAllProjectIdsByCookieProviderIdQuery::create($cookieProviderId));
    }

    /**
     * @return array<string>
     */
    private function getAllProjectIdsByCookieId(string $cookieId): array
    {
        $cookieProviderId = $this->queryBus->dispatch(GetCookieProviderIdByCookieIdQuery::create($cookieId));

        return $cookieProviderId ? $this->getAllProjectIdsByCookieProviderId($cookieProviderId) : [];
    }

    /**
     * @return array<string>
     */
    private function getAllProjectIdsByCookieSuggestionId(string $cookieSuggestionId): array
    {
        $cookieSuggestion = $this->queryBus->dispatch(GetCookieSuggestionByIdQuery::create($cookieSuggestionId));

        return $cookieSuggestion instanceof CookieSuggestion ? [$cookieSuggestion->projectId] : [];
    }
}
