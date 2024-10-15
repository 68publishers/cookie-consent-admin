<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use App\Domain\Project\Exception\InvalidCodeException;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractStringValueObject;

final class Code extends AbstractStringValueObject
{
    public const int MAX_LENGTH = 70;

    public static function fromValidCode(string $code): self
    {
        if (!preg_match('/^[a-z0-9_\-\.]+$/', $code)) {
            throw InvalidCodeException::containsNonAllowedCharacters($code);
        }

        if (self::MAX_LENGTH < mb_strlen($code)) {
            throw InvalidCodeException::tooLong($code, self::MAX_LENGTH);
        }

        return self::fromValue($code);
    }
}
