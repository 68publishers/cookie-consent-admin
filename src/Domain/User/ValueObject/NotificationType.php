<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractEnumValueObject;

final class NotificationType extends AbstractEnumValueObject
{
    public const string CONSENT_DECREASED = 'consent_decreased';
    public const string WEEKLY_OVERVIEW = 'weekly_overview';
    public const string COOKIE_SUGGESTIONS = 'cookie_suggestions';

    public static function values(): array
    {
        return [
            self::CONSENT_DECREASED,
            self::WEEKLY_OVERVIEW,
            self::COOKIE_SUGGESTIONS,
        ];
    }
}
