<?php

declare(strict_types=1);

namespace App\Application\GlobalSettings;

use App\Domain\Shared\ValueObject\Locale as LocaleValueObject;
use App\Domain\Shared\ValueObject\LocalesConfig;

final readonly class ValidLocalesProvider
{
    public function __construct(
        private GlobalSettingsInterface $globalSettings,
        private ?LocalesConfig $localesConfig = null,
    ) {}

    /**
     * @return array<Locale>
     */
    public function getValidLocales(?LocalesConfig $localesConfig = null): array
    {
        $globalLocales = $this->globalSettings->locales();
        $localesConfig = $localesConfig ?? $this->localesConfig;

        if (null === $localesConfig) {
            return $globalLocales;
        }

        $validLocales = [];

        foreach ($localesConfig->locales()->all() as $localeVo) {
            assert($localeVo instanceof LocaleValueObject);

            foreach ($globalLocales as $globalLocale) {
                if ($localeVo->value() === $globalLocale->code()) {
                    $validLocales[] = $globalLocale;

                    continue 2;
                }
            }
        }

        return $validLocales;
    }

    public function getValidDefaultLocale(?LocalesConfig $localesConfig = null): ?Locale
    {
        $localesConfig = $localesConfig ?? $this->localesConfig;

        if (null === $localesConfig) {
            $defaultGlobalLocale = $this->globalSettings->defaultLocale();

            return 'unknown' !== $defaultGlobalLocale->code() ? $defaultGlobalLocale : null;
        }

        $defaultLocale = $localesConfig->defaultLocale();

        foreach ($this->globalSettings->locales() as $globalLocale) {
            if ($defaultLocale->value() === $globalLocale->code()) {
                return $globalLocale;
            }
        }

        return null;
    }

    public function withLocalesConfig(?LocalesConfig $localesConfig): self
    {
        return new self($this->globalSettings, $localesConfig);
    }
}
