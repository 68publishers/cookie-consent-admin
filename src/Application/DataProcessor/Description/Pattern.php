<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Description;

use App\Application\DataProcessor\Context\ContextInterface;
use Nette\Schema\Elements\Type;

final class Pattern implements TypeDescriptorPropertyInterface
{
    public function __construct(
        private readonly string $pattern,
    ) {}

    public function applyToType(Type $type, ContextInterface $context): Type
    {
        return $type->pattern($this->pattern);
    }
}
