<?php

declare(strict_types=1);

namespace App\Domain\GlobalSettings\CommandHandler;

use App\Domain\GlobalSettings\Command\PutCrawlerSettingsCommand;
use App\Domain\GlobalSettings\GlobalSettings;
use App\Domain\GlobalSettings\GlobalSettingsRepositoryInterface;
use App\Domain\GlobalSettings\ValueObject\CrawlerSettings;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class PutCrawlerSettingsCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private GlobalSettingsRepositoryInterface $globalSettingsRepository,
    ) {}

    public function __invoke(PutCrawlerSettingsCommand $command): void
    {
        $globalSettings = $this->globalSettingsRepository->get();

        if (!$globalSettings instanceof GlobalSettings) {
            $globalSettings = GlobalSettings::createEmpty();
        }

        $globalSettings->updateCrawlerSettings(CrawlerSettings::fromValues(
            $command->enabled(),
            $command->hostUrl(),
            $command->username(),
            $command->password(),
            $command->callbackUriToken(),
        ));

        $this->globalSettingsRepository->save($globalSettings);
    }
}
