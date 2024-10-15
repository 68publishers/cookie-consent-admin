<?php

declare(strict_types=1);

namespace App\Application\Import;

final class ImportState
{
    public const string STATUS_RUNNING = 'running';
    public const string STATUS_COMPLETED = 'completed';
    public const string STATUS_FAILED = 'failed';

    public array $importedIndexes = [];

    public array $failedIndexes = [];

    public int $warningsTotal = 0;

    public string $output = '';

    public string $status = self::STATUS_RUNNING;

    public function __construct(
        public string $id,
    ) {}

    public function importedTotal(): int
    {
        return count($this->importedIndexes);
    }

    public function failedTotal(): int
    {
        return count($this->failedIndexes);
    }

    public function addImported(string $index): void
    {
        if (!in_array($index, $this->importedIndexes, true)) {
            $this->importedIndexes[] = $index;
        }
    }

    public function addFailed(string $index): void
    {
        if (!in_array($index, $this->failedIndexes, true)) {
            $this->failedIndexes[] = $index;
        }
    }

    public function resolveStatus(): void
    {
        if (self::STATUS_RUNNING === $this->status) {
            $this->status = 0 === $this->importedTotal() && 0 < $this->failedTotal() ? self::STATUS_FAILED : self::STATUS_COMPLETED;
        }
    }
}
