<?php

declare(strict_types=1);

namespace App\Domain\GlobalSettings\CommandHandler;

use App\Domain\GlobalSettings\Command\PutAzureAuthSettingsCommand;
use App\Domain\GlobalSettings\GlobalSettings;
use App\Domain\GlobalSettings\GlobalSettingsRepositoryInterface;
use App\Domain\GlobalSettings\ValueObject\AzureAuthSettings;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class PutAzureAuthSettingsCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private GlobalSettingsRepositoryInterface $globalSettingsRepository,
    ) {}

    public function __invoke(PutAzureAuthSettingsCommand $command): void
    {
        $globalSettings = $this->globalSettingsRepository->get();

        if (!$globalSettings instanceof GlobalSettings) {
            $globalSettings = GlobalSettings::createEmpty();
        }

        $globalSettings->updateAzureAuthSettings(AzureAuthSettings::fromValues(
            enabled: $command->enabled(),
            clientId: $command->clientId(),
            clientSecret: $command->clientSecret(),
            tenantId: $command->tenantId(),
        ));

        $this->globalSettingsRepository->save($globalSettings);
    }
}
