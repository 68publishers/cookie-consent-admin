<?php

declare(strict_types=1);

namespace App\Domain\ConsentSettings\ValueObject;

use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractValueObjectSet;

final class SettingsGroup extends AbstractValueObjectSet
{
    public const string ITEM_CLASSNAME = Settings::class;

    protected static function reconstituteItem($value): Settings
    {
        return Settings::fromArray($value);
    }

    /**
     * @param Settings $item
     */
    protected static function exportItem($item): array
    {
        assert($item instanceof Settings);

        return $item->values();
    }
}
