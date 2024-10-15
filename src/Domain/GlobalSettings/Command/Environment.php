<?php

declare(strict_types=1);

namespace App\Domain\GlobalSettings\Command;

final readonly class Environment
{
    public function __construct(
        public string $code,
        public string $name,
        public string $color,
    ) {}
}
