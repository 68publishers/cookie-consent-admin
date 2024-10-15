<?php

declare(strict_types=1);

namespace App\Subscribers\Project;

use App\Domain\Project\Command\UpdateProjectTemplatesCommand;
use App\Domain\Project\Event\ProjectCreated;
use Psr\Log\LoggerInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Event\EventHandlerInterface;

final readonly class CreateDefaultTemplateWhenProjectCreated implements EventHandlerInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ProjectCreated $event): void
    {
        $filename = __DIR__ . '/resources/defaultTemplate.latte';
        $defaultTemplate = @file_get_contents($filename);

        if (false === $defaultTemplate) {
            $this->logger->error(sprintf(
                'Cant\'t load default template %s',
                $filename,
            ));

            return;
        }

        $command = UpdateProjectTemplatesCommand::create($event->projectId()->toString())
            ->withTemplate($event->locales()->defaultLocale()->value(), $defaultTemplate);

        $this->commandBus->dispatch($command);
    }
}
