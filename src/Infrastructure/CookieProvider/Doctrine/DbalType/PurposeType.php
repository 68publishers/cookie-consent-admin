<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieProvider\Doctrine\DbalType;

use App\Domain\CookieProvider\ValueObject\Purpose;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\DbalType\AbstractTextValueObjectType;

final class PurposeType extends AbstractTextValueObjectType
{
	protected string $valueObjectClassname = Purpose::class;
}
