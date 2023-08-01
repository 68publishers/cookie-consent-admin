<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Read\Reader;

use App\Application\DataProcessor\Exception\ReaderException;
use App\Application\DataProcessor\Read\Resource\ResourceInterface;

interface ReaderFactoryInterface
{
    public function accepts(string $format, ResourceInterface $resource): bool;

    /**
     * @throws ReaderException
     */
    public function create(ResourceInterface $resource): ReaderInterface;
}
