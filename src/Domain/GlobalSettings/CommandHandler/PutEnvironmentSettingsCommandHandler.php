<?php

declare(strict_types=1);

namespace App\Domain\GlobalSettings\CommandHandler;

use App\Domain\GlobalSettings\Command\Environment;
use App\Domain\GlobalSettings\Command\PutEnvironmentSettingsCommand;
use App\Domain\GlobalSettings\GlobalSettings;
use App\Domain\GlobalSettings\GlobalSettingsRepositoryInterface;
use App\Domain\GlobalSettings\ValueObject\EnvironmentSettings;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class PutEnvironmentSettingsCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private GlobalSettingsRepositoryInterface $globalSettingsRepository,
    ) {}

    public function __invoke(PutEnvironmentSettingsCommand $command): void
    {
        $globalSettings = $this->globalSettingsRepository->get();

        if (!$globalSettings instanceof GlobalSettings) {
            $globalSettings = GlobalSettings::createEmpty();
        }

        $globalSettings->updateEnvironmentSettings(EnvironmentSettings::fromNative([
            'default_environment' => [
                'name' => $command->defaultEnvironmentName(),
                'color' => $command->defaultEnvironmentColor(),
            ],
            'environments' => array_map(
                static fn (Environment $environment): array => [
                    'code' => $environment->code,
                    'name' => $environment->name,
                    'color' => $environment->color,
                ],
                $command->environments(),
            ),
        ]));

        $this->globalSettingsRepository->save($globalSettings);
    }
}
