<?php

declare(strict_types=1);

namespace App\Domain\GlobalSettings\CommandHandler;

use App\Domain\GlobalSettings\Command\PutApiCacheSettingsCommand;
use App\Domain\GlobalSettings\GlobalSettings;
use App\Domain\GlobalSettings\GlobalSettingsRepositoryInterface;
use App\Domain\GlobalSettings\ValueObject\ApiCache;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class PutApiCacheSettingsCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private GlobalSettingsRepositoryInterface $globalSettingsRepository,
    ) {}

    public function __invoke(PutApiCacheSettingsCommand $command): void
    {
        $globalSettings = $this->globalSettingsRepository->get();

        if (!$globalSettings instanceof GlobalSettings) {
            $globalSettings = GlobalSettings::createEmpty();
        }

        $globalSettings->updateApiCacheSettings(ApiCache::create($command->cacheControlDirectives(), $command->useEntityTag()));

        $this->globalSettingsRepository->save($globalSettings);
    }
}
