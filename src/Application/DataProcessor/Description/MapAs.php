<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Description;

use App\Application\DataProcessor\Context\ContextInterface;
use Nette\Schema\Elements\Structure;

final readonly class MapAs implements StructureDescriptorPropertyInterface
{
    public function __construct(
        private string $classname,
    ) {}

    public function applyToStructure(Structure $structure, ContextInterface $context): Structure
    {
        return $structure->castTo($this->classname);
    }
}
