<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Doctrine\DbalType;

use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use Throwable;

final class DateTimeZoneType extends StringType
{
    public const string NAME = 'datetime_zone';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?DateTimeZone
    {
        if (null === $value || $value instanceof DateTimeZone) {
            return $value;
        }

        try {
            $dateTimeZone = new DateTimeZone($value);
        } catch (Throwable $e) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                'valid timezone name',
            );
        }

        return $dateTimeZone;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof DateTimeZone) {
            return $value->getName();
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            ['null', DateTimeZone::class],
        );
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
