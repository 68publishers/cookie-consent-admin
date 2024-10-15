<?php

declare(strict_types=1);

namespace App\Domain\Import\ValueObject;

use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractEnumValueObject;

final class Status extends AbstractEnumValueObject
{
    public const string RUNNING = 'running';
    public const string FAILED = 'failed';
    public const string COMPLETED = 'completed';

    public static function values(): array
    {
        return [
            self::RUNNING,
            self::FAILED,
            self::COMPLETED,
        ];
    }

    public static function running(): self
    {
        return self::fromValue(self::RUNNING);
    }

    public static function failed(): self
    {
        return self::fromValue(self::FAILED);
    }

    public static function completed(): self
    {
        return self::fromValue(self::COMPLETED);
    }
}
