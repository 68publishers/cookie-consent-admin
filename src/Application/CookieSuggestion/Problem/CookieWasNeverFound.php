<?php

declare(strict_types=1);

namespace App\Application\CookieSuggestion\Problem;

use App\Application\CookieSuggestion\Solution\Solutions;

final class CookieWasNeverFound implements ProblemInterface
{
    public const string TYPE = 'cookie_was_never_found';

    public function __construct(
        private readonly Solutions $solutions,
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getTranslatorArgs(): array
    {
        return [];
    }

    public function getSolutions(): Solutions
    {
        return $this->solutions;
    }
}
