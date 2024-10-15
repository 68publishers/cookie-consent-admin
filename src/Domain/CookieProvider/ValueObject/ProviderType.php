<?php

declare(strict_types=1);

namespace App\Domain\CookieProvider\ValueObject;

use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractEnumValueObject;

final class ProviderType extends AbstractEnumValueObject
{
    public const string FIRST_PARTY = '1st_party';
    public const string THIRD_PARTY = '3rd_party';

    public static function values(): array
    {
        return [
            self::FIRST_PARTY,
            self::THIRD_PARTY,
        ];
    }
}
