<?php

declare(strict_types=1);

namespace App\Infrastructure\ConsentSettings\Doctrine;

use App\Domain\ConsentSettings\ShortIdentifierGeneratorInterface;
use App\Domain\ConsentSettings\ValueObject\ShortIdentifier;
use App\Domain\Project\ValueObject\ProjectId;
use App\ReadModel\ConsentSettings\GetLatestShortIdentifierQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class ShortIdentifierGenerator implements ShortIdentifierGeneratorInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function generate(ProjectId $projectId): ShortIdentifier
    {
        return ShortIdentifier::fromValue(
            $this->queryBus->dispatch(GetLatestShortIdentifierQuery::create($projectId->toString())) + 1,
        );
    }
}
