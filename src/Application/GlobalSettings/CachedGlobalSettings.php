<?php

declare(strict_types=1);

namespace App\Application\GlobalSettings;

use App\Domain\GlobalSettings\ValueObject\ApiCache;
use App\Domain\GlobalSettings\ValueObject\AzureAuthSettings;
use App\Domain\GlobalSettings\ValueObject\CrawlerSettings;
use App\Domain\GlobalSettings\ValueObject\EnvironmentSettings;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use SixtyEightPublishers\TranslationBridge\Localization\TranslatorLocalizerInterface;
use Throwable;

final class CachedGlobalSettings implements GlobalSettingsInterface
{
    private const string CACHE_KEY = 'global_settings';

    private Cache $cache;

    private ?GlobalSettingsInterface $inner = null;

    public function __construct(
        private readonly GlobalSettingsFactoryInterface $globalSettingsFactory,
        Storage $storage,
        private readonly TranslatorLocalizerInterface $translatorLocalizer,
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * @throws Throwable
     */
    public function locales(): array
    {
        return $this->getInner()->locales();
    }

    /**
     * @throws Throwable
     */
    public function defaultLocale(): Locale
    {
        return $this->getInner()->defaultLocale();
    }

    /**
     * @throws Throwable
     */
    public function apiCache(): ApiCache
    {
        return $this->getInner()->apiCache();
    }

    /**
     * @throws Throwable
     */
    public function crawlerSettings(): CrawlerSettings
    {
        return $this->getInner()->crawlerSettings();
    }

    /**
     * @throws Throwable
     */
    public function environmentSettings(): EnvironmentSettings
    {
        return $this->getInner()->environmentSettings();
    }

    /**
     * @throws Throwable
     */
    public function azureAuthSettings(): AzureAuthSettings
    {
        return $this->getInner()->azureAuthSettings();
    }

    public function refresh(): void
    {
        $this->inner?->refresh();

        $this->cache->clean([
            Cache::All => true,
        ]);
        $this->inner = null;
    }

    /**
     * @throws Throwable
     */
    private function getInner(): GlobalSettingsInterface
    {
        return $this->inner ?? ($this->inner = $this->cache->load($this->createKey(), function (): GlobalSettingsInterface {
            return $this->globalSettingsFactory->create();
        }));
    }

    private function createKey(): string
    {
        return self::CACHE_KEY . '_' . $this->translatorLocalizer->getLocale();
    }
}
