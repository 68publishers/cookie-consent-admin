<?php

declare(strict_types=1);

namespace App\Application\GlobalSettings;

use App\Domain\GlobalSettings\ValueObject\ApiCache;
use App\Domain\GlobalSettings\ValueObject\AzureAuthSettings;
use App\Domain\GlobalSettings\ValueObject\CrawlerSettings;
use App\Domain\GlobalSettings\ValueObject\EnvironmentSettings;

final readonly class GlobalSettings implements GlobalSettingsInterface
{
    /**
     * @param array<Locale> $locales
     */
    public function __construct(
        private array $locales,
        private Locale $defaultLocale,
        private ApiCache $apiCache,
        private CrawlerSettings $crawlerSettings,
        private EnvironmentSettings $environmentSettings,
        private AzureAuthSettings $azureAuthSettings,
    ) {}

    public static function default(): self
    {
        return new self(
            [],
            Locale::unknown(),
            ApiCache::create(),
            CrawlerSettings::fromValues(false, null, null, null, null),
            EnvironmentSettings::createDefault(),
            AzureAuthSettings::fromValues(false, null, null, null),
        );
    }

    public function locales(): array
    {
        return $this->locales;
    }

    public function defaultLocale(): Locale
    {
        return $this->defaultLocale;
    }

    public function apiCache(): ApiCache
    {
        return $this->apiCache;
    }

    public function crawlerSettings(): CrawlerSettings
    {
        return $this->crawlerSettings;
    }

    public function environmentSettings(): EnvironmentSettings
    {
        return $this->environmentSettings;
    }

    public function azureAuthSettings(): AzureAuthSettings
    {
        return $this->azureAuthSettings;
    }

    public function refresh(): void
    {
    }
}
