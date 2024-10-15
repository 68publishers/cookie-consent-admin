<?php

declare(strict_types=1);

namespace App\Infrastructure\Project;

use App\Domain\Project\CheckCodeUniquenessInterface;
use App\Domain\Project\Exception\CodeUniquenessException;
use App\Domain\Project\ValueObject\Code;
use App\Domain\Project\ValueObject\ProjectId;
use App\ReadModel\Project\GetProjectByCodeQuery;
use App\ReadModel\Project\ProjectView;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckCodeUniqueness implements CheckCodeUniquenessInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(ProjectId $projectId, Code $code): void
    {
        $projectView = $this->queryBus->dispatch(GetProjectByCodeQuery::create($code->value()));

        if (!$projectView instanceof ProjectView) {
            return;
        }

        if (!$projectView->id->equals($projectId)) {
            throw CodeUniquenessException::create($code->value());
        }
    }
}
