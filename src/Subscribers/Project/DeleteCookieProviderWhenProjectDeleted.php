<?php

declare(strict_types=1);

namespace App\Subscribers\Project;

use App\Domain\CookieProvider\Command\DeleteCookieProviderCommand;
use App\Domain\CookieProvider\Exception\CookieProviderNotFoundException;
use App\Domain\Project\Project;
use App\ReadModel\Project\GetProjectByIdQuery;
use App\ReadModel\Project\ProjectView;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Domain\Event\AggregateDeleted;
use SixtyEightPublishers\ArchitectureBundle\Event\EventHandlerInterface;

final readonly class DeleteCookieProviderWhenProjectDeleted implements EventHandlerInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(AggregateDeleted $event): void
    {
        if (Project::class !== $event->aggregateClassname()) {
            return;
        }

        $projectView = $this->queryBus->dispatch(GetProjectByIdQuery::create($event->aggregateId()->toString()));

        if (!$projectView instanceof ProjectView) {
            return;
        }

        try {
            $this->commandBus->dispatch(DeleteCookieProviderCommand::create($projectView->cookieProviderId->toString()));
        } catch (CookieProviderNotFoundException $e) {
        }
    }
}
