<?php

declare(strict_types=1);

namespace App\Application\Import;

final class ImporterResult
{
    /** @var RowResult[] */
    private array $rows = [];

    private function __construct() {}

    /**
     * @return static
     */
    public static function of(RowResult ...$rows): self
    {
        $result = new self();
        $result->rows = $rows;

        return $result;
    }

    /**
     * @return $this
     */
    public function with(RowResult $rowResult): self
    {
        $rows = $this->rows;
        $rows[] = $rowResult;

        return self::of(...$rows);
    }

    /**
     * @return $this
     */
    public function merge(self $importerResult): self
    {
        $rows = array_merge($this->rows, $importerResult->all());

        return self::of(...$rows);
    }

    /**
     * @return RowResult[]
     */
    public function all(): array
    {
        return $this->rows;
    }

    public function each(callable $callback): void
    {
        foreach ($this->rows as $row) {
            $callback($row);
        }
    }
}
