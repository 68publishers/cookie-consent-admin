<?php

declare(strict_types=1);

namespace App\Domain\Cookie\ValueObject;

use App\Application\Helper\Estimate;
use App\Domain\Cookie\Exception\InvalidProcessingTimeException;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractStringValueObject;

final class ProcessingTime extends AbstractStringValueObject
{
    public const string PERSISTENT = 'persistent';
    public const string SESSION = 'session';

    /**
     * @throws InvalidProcessingTimeException
     */
    public static function withValidation(string $value): self
    {
        if (self::PERSISTENT !== $value && self::SESSION !== $value && !Estimate::isMaskValid($value)) {
            throw InvalidProcessingTimeException::invalidValue($value);
        }

        return self::fromValue($value);
    }

    public function print(string $locale, ?string $fallbackLocale = null): string
    {
        $value = $this->value();

        if (self::PERSISTENT === $value || self::SESSION === $value) {
            return $value;
        }

        return Estimate::fromMask($value, $locale, $fallbackLocale);
    }
}
