<?php

declare(strict_types=1);

namespace App\ReadModel\User;

use DateTimeImmutable;

final readonly class ExternalAuthView
{
    public function __construct(
        public string $userId,
        public string $providerCode,
        public ?DateTimeImmutable $createdAt,
        public string $resourceOwnerId,
    ) {}
}
