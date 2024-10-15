<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Read\Reader;

use App\Application\DataProcessor\Exception\ReaderException;
use App\Application\DataProcessor\Read\Resource\QueryResource;
use App\Application\DataProcessor\Read\Resource\ResourceInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class QueryReaderFactory implements ReaderFactoryInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function accepts(string $format, ResourceInterface $resource): bool
    {
        return $resource instanceof QueryResource; // ignore a format
    }

    public function create(ResourceInterface $resource): ReaderInterface
    {
        if ($resource instanceof QueryResource) {
            return QueryReader::create($this->queryBus, $resource);
        }

        throw ReaderException::unacceptableResource('query', $resource);
    }
}
