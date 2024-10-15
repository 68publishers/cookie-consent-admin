<?php

declare(strict_types=1);

namespace App\Domain\CookieSuggestion\ValueObject;

use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractValueObjectSet;

final class AcceptedCategories extends AbstractValueObjectSet
{
    public const string ITEM_CLASSNAME = CategoryCode::class;

    protected static function reconstituteItem($value): CategoryCode
    {
        return CategoryCode::fromValue($value);
    }

    protected static function exportItem($item): string
    {
        assert($item instanceof CategoryCode);

        return $item->value();
    }
}
