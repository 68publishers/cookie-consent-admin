<?php

declare(strict_types=1);

namespace App\Domain\Cookie\ValueObject;

use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractValueObjectSet;

final class Environments extends AbstractValueObjectSet
{
    public const string ITEM_CLASSNAME = Environment::class;

    /**
     * @param string $value
     */
    protected static function reconstituteItem($value): Environment
    {
        return Environment::fromValue($value);
    }

    /**
     * @param Environment $item
     */
    protected static function exportItem($item): ?string
    {
        assert($item instanceof Environment);

        return $item->value();
    }
}
