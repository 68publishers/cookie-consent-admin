<?php

declare(strict_types=1);

namespace App\ReadModel\Cookie;

final readonly class CookieProjectItem
{
    public function __construct(
        public string $name,
        public string $color,
    ) {}
}
