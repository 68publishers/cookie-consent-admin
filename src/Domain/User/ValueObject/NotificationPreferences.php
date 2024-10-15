<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractValueObjectSet;

final class NotificationPreferences extends AbstractValueObjectSet
{
    public const string ITEM_CLASSNAME = NotificationType::class;

    /**
     * @param string $value
     */
    protected static function reconstituteItem($value): NotificationType
    {
        return NotificationType::fromValue($value);
    }

    /**
     * @param NotificationType $item
     */
    protected static function exportItem($item): string
    {
        assert($item instanceof NotificationType);

        return $item->value();
    }
}
