<?php

declare(strict_types=1);

namespace App\Infrastructure\ConsentSettings;

use App\Domain\ConsentSettings\CheckChecksumNotExistsInterface;
use App\Domain\ConsentSettings\Exception\ChecksumExistsException;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\ValueObject\Checksum;
use App\ReadModel\ConsentSettings\ConsentSettingsView;
use App\ReadModel\ConsentSettings\GetConsentSettingsByProjectIdAndChecksumQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckChecksumNotExists implements CheckChecksumNotExistsInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(ProjectId $projectId, Checksum $checksum): void
    {
        $consentSettingsView = $this->queryBus->dispatch(GetConsentSettingsByProjectIdAndChecksumQuery::create($projectId->toString(), $checksum->value()));

        if ($consentSettingsView instanceof ConsentSettingsView) {
            throw ChecksumExistsException::create($consentSettingsView->id, $consentSettingsView->checksum);
        }
    }
}
