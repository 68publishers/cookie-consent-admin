<?php

declare(strict_types=1);

namespace App\Application\DataProcessor;

use App\Application\DataProcessor\Read\DataReaderFactoryInterface;
use App\Application\DataProcessor\Read\Resource\ResourceInterface;
use App\Application\DataProcessor\Write\DataWriterFactoryInterface;

final readonly class DataProcessFactory
{
    public function __construct(
        private DataReaderFactoryInterface $dataReaderFactory,
        private DataWriterFactoryInterface $dataWriterFactory,
    ) {}

    public function fromResource(string $format, ResourceInterface $resource): WriteProcess
    {
        return new WriteProcess($this->dataWriterFactory, $this->dataReaderFactory->fromResource($format, $resource));
    }

    public function fromFile(string $format, string $filename, array $options = []): WriteProcess
    {
        return new WriteProcess($this->dataWriterFactory, $this->dataReaderFactory->fromFile($format, $filename, $options));
    }

    public function fromString(string $format, string $string, array $options = []): WriteProcess
    {
        return new WriteProcess($this->dataWriterFactory, $this->dataReaderFactory->fromString($format, $string, $options));
    }

    public function fromArray(array $data, array $options = []): WriteProcess
    {
        return new WriteProcess($this->dataWriterFactory, $this->dataReaderFactory->fromArray($data, $options));
    }
}
