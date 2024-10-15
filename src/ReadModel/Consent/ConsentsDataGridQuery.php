<?php

declare(strict_types=1);

namespace App\ReadModel\Consent;

use App\ReadModel\AbstractDataGridQuery;

final class ConsentsDataGridQuery extends AbstractDataGridQuery
{
    private const int DEFAULT_COUNT_LIMIT = 100_000;

    public static function create(
        string $projectId,
        ?int $countLimit = null,
    ): self {
        return self::fromParameters([
            'project_id' => $projectId,
            'count_limit' => $countLimit,
        ]);
    }

    public function projectId(): string
    {
        return $this->getParam('project_id');
    }

    public function getCountLimit(): int
    {
        return $this->getParam('count_limit') ?? self::DEFAULT_COUNT_LIMIT;
    }
}
