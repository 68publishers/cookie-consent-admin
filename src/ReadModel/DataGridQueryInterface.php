<?php

declare(strict_types=1);

namespace App\ReadModel;

use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\PaginatedQueryInterface;

interface DataGridQueryInterface extends PaginatedQueryInterface
{
    public const string MODE_DATA = 'data';
    public const string MODE_ONE = 'one';
    public const string MODE_COUNT = 'count';

    public function filters(): array;

    public function sorting(): array;

    public function mode(): string;

    public function withFilter(string $name, $value): self;

    public function withSorting(string $name, string $direction): self;

    public function withDataMode(): self;

    public function withOneMode(): self;

    public function withCountMode(): self;
}
