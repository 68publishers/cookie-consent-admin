<?php

declare(strict_types=1);

namespace App\Application\GlobalSettings;

use App\Application\Localization\Locales;
use App\Domain\Shared\ValueObject\Locale as LocaleValueObject;
use App\ReadModel\GlobalSettings\GetGlobalSettingsQuery;
use App\ReadModel\GlobalSettings\GlobalSettingsView;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class GlobalSettingsFactory implements GlobalSettingsFactoryInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Locales $locales,
    ) {}

    public function create(): GlobalSettingsInterface
    {
        $globalSettingsView = $this->queryBus->dispatch(GetGlobalSettingsQuery::create());

        if (!$globalSettingsView instanceof GlobalSettingsView) {
            return GlobalSettings::default();
        }

        $locales = [];
        $list = $this->locales->get();

        foreach ($globalSettingsView->locales->locales()->all() as $locale) {
            assert($locale instanceof LocaleValueObject);

            $localeValue = $locale->value();
            $locales[] = Locale::create($localeValue, $list[$localeValue] ?? $localeValue);
        }

        $defaultLocaleValue = $globalSettingsView->locales->defaultLocale()->value();

        return new GlobalSettings(
            locales: $locales,
            defaultLocale: Locale::create($defaultLocaleValue, $list[$defaultLocaleValue] ?? $defaultLocaleValue),
            apiCache: $globalSettingsView->apiCache,
            crawlerSettings: $globalSettingsView->crawlerSettings,
            environmentSettings: $globalSettingsView->environmentSettings,
            azureAuthSettings: $globalSettingsView->azureAuthSettings,
        );
    }
}
