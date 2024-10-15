<?php

declare(strict_types=1);

namespace App\Application\CookieSuggestion\Problem;

use App\Application\CookieSuggestion\Solution\Solutions;

final class CookieLongTimeNotFound implements ProblemInterface
{
    public const string TYPE = 'cookie_long_time_not_found';

    public function __construct(
        private readonly int $notFoundForDays,
        private readonly Solutions $solutions,
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getTranslatorArgs(): array
    {
        return [
            'not_found_for_days' => $this->notFoundForDays,
        ];
    }

    public function getSolutions(): Solutions
    {
        return $this->solutions;
    }
}
