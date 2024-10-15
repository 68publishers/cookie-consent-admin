<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Context;

use ArrayAccess;

interface ContextInterface extends ArrayAccess
{
    public const string WEAK_TYPES = 'weak_types';

    public static function default(array $array): self;

    public static function fromArray(array $array): self;
}
