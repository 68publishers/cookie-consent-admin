<?php

declare(strict_types=1);

namespace App\Application\CookieSuggestion\Problem;

use App\Application\CookieSuggestion\Solution\Solutions;
use App\Application\CookieSuggestion\Suggestion\CookieOccurrence;

final class CookieIsInCategoryThatIsNotAcceptedByScenario implements ProblemInterface
{
    public const string TYPE = 'cookie_is_in_category_that_is_not_accepted';

    /**
     * @param array<int, string> $cookieCategories
     * @param array<int, string> $acceptedCategories
     */
    public function __construct(
        public array $cookieCategories,
        public array $acceptedCategories,
        public CookieOccurrence $occurrence,
        private readonly Solutions $solutions,
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getTranslatorArgs(): array
    {
        return [
            'cookieCategories' => implode(', ', $this->cookieCategories),
            'acceptedCategories' => implode(', ', $this->acceptedCategories),
            'scenarioName' => $this->occurrence->scenarioName,
        ];
    }

    public function getSolutions(): Solutions
    {
        return $this->solutions;
    }
}
