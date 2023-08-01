<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Description;

use App\Application\DataProcessor\Context\ContextInterface;
use Nette\Schema\Elements\Type;

final class Deprecated implements TypeDescriptorPropertyInterface
{
    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function applyToType(Type $type, ContextInterface $context): Type
    {
        return $type->deprecated($this->message);
    }
}
