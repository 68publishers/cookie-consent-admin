<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractValueObjectSet;

final class Locales extends AbstractValueObjectSet
{
    public const string ITEM_CLASSNAME = Locale::class;

    /**
     * @param string $value
     */
    protected static function reconstituteItem($value): Locale
    {
        return Locale::fromValue($value);
    }

    /**
     * @param Locale $item
     */
    protected static function exportItem($item): string
    {
        assert($item instanceof Locale);

        return $item->value();
    }
}
