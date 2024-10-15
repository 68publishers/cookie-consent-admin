<?php

declare(strict_types=1);

namespace App\Domain\ConsentSettings\CommandHandler;

use App\Domain\ConsentSettings\CheckChecksumNotExistsInterface;
use App\Domain\ConsentSettings\Command\StoreConsentSettingsCommand;
use App\Domain\ConsentSettings\ConsentSettings;
use App\Domain\ConsentSettings\ConsentSettingsRepositoryInterface;
use App\Domain\ConsentSettings\Exception\ChecksumExistsException;
use App\Domain\ConsentSettings\ShortIdentifierGeneratorInterface;
use App\Domain\ConsentSettings\ValueObject\Settings;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Checksum;
use Exception;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class StoreConsentSettingsCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ConsentSettingsRepositoryInterface $consentSettingsRepository,
        private CheckChecksumNotExistsInterface $checkChecksumNotExists,
        private ShortIdentifierGeneratorInterface $shortIdentifierGenerator,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(StoreConsentSettingsCommand $command): void
    {
        $projectId = ProjectId::fromString($command->projectId());
        $checksum = Checksum::fromValue($command->checksum());
        $settings = Settings::create($command->setting());

        try {
            $consentSettings = ConsentSettings::create($projectId, $checksum, $settings, $this->checkChecksumNotExists, $this->shortIdentifierGenerator);
        } catch (ChecksumExistsException $e) {
            $consentSettings = $this->consentSettingsRepository->get($e->consentSettingsId());
            $consentSettings->addSettings($settings);
        }

        $this->consentSettingsRepository->save($consentSettings);
    }
}
