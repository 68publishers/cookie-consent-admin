<?php

declare(strict_types=1);

namespace App\Application\CookieSuggestion\Solution;

final readonly class ChangeCookieCategory implements SolutionInterface
{
    public function __construct(
        private string $existingCookieId,
        private string $categoryCode,
        private string $providerName,
    ) {}

    public function getType(): string
    {
        return 'change_cookie_category';
    }

    public function getUniqueId(): string
    {
        return md5($this->getType() . '__x__' . $this->existingCookieId);
    }

    public function getArguments(): array
    {
        return [
            'existing_cookie_id' => $this->existingCookieId,
        ];
    }

    public function getTranslatorArgs(): array
    {
        return [
            'category_code' => $this->categoryCode,
            'provider_name' => $this->providerName,
        ];
    }
}
