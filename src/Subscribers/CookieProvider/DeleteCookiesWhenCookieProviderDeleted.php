<?php

declare(strict_types=1);

namespace App\Subscribers\CookieProvider;

use App\Domain\Cookie\Command\DeleteCookieCommand;
use App\Domain\Cookie\Exception\CookieNotFoundException;
use App\Domain\CookieProvider\CookieProvider;
use App\ReadModel\Cookie\CookieView;
use App\ReadModel\Cookie\FindCookiesByCookieProviderQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Domain\Event\AggregateDeleted;
use SixtyEightPublishers\ArchitectureBundle\Event\EventHandlerInterface;

final readonly class DeleteCookiesWhenCookieProviderDeleted implements EventHandlerInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(AggregateDeleted $event): void
    {
        if (CookieProvider::class !== $event->aggregateClassname()) {
            return;
        }

        $cookieViews = $this->queryBus->dispatch(FindCookiesByCookieProviderQuery::create($event->aggregateId()->toString()));

        foreach ($cookieViews as $cookieView) {
            assert($cookieView instanceof CookieView);

            try {
                $this->commandBus->dispatch(DeleteCookieCommand::create($cookieView->id->toString()));
            } catch (CookieNotFoundException $e) {
            }
        }
    }
}
