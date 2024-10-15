<?php

declare(strict_types=1);

namespace App\Application\CookieSuggestion\Solution;

final readonly class AssociateCookieProviderWithProject implements SolutionInterface
{
    public function __construct(
        private string $providerId,
        private string $providerName,
    ) {}

    public function getType(): string
    {
        return 'associate_cookie_provider_with_project';
    }

    public function getUniqueId(): string
    {
        return md5($this->getType() . '__x__' . $this->providerId);
    }

    public function getArguments(): array
    {
        return [
            'provider_id' => $this->providerId,
        ];
    }

    public function getTranslatorArgs(): array
    {
        return [
            'provider_name' => $this->providerName,
        ];
    }
}
