<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Write\Writer;

use App\Application\DataProcessor\RowInterface;
use App\Application\DataProcessor\Write\Destination\DestinationInterface;
use App\Application\DataProcessor\Write\Destination\FileDestination;
use App\Application\DataProcessor\Write\Destination\StringDestination;
use App\Application\DataProcessor\Write\Helper\FilePutContents;
use App\Application\DataProcessor\Write\Resource\ResourceInterface;
use JsonException;

final class JsonWriter extends AbstractWriter
{
    public const string OPTION_PRETTY = 'pretty';
    public const string OPTION_UNESCAPED_UNICODE = 'unescaped_unicode';

    private array $data = [];

    public static function fromFile(ResourceInterface $resource, FileDestination $destination): self
    {
        return new self($resource, $destination);
    }

    public static function fromString(ResourceInterface $resource, StringDestination $destination): self
    {
        return new self($resource, $destination);
    }

    protected function prepare(): void
    {
        $this->data = [];
    }

    protected function processRow(RowInterface $row, DestinationInterface $destination): DestinationInterface
    {
        $this->data[] = $row->data()->toArray();

        return $destination;
    }

    /**
     * @throws JsonException
     */
    protected function finish(DestinationInterface $destination): DestinationInterface
    {
        $flags = 0;

        if (true === ($destination->options()[self::OPTION_PRETTY] ?? false)) {
            $flags |= JSON_PRETTY_PRINT;
        }

        if (true === ($destination->options()[self::OPTION_UNESCAPED_UNICODE] ?? false)) {
            $flags |= JSON_UNESCAPED_UNICODE;
        }

        $json = json_encode($this->data, $flags | JSON_THROW_ON_ERROR);
        $this->data = [];

        if ($destination instanceof StringDestination) {
            return $destination->append($json);
        }

        assert($destination instanceof FileDestination);

        FilePutContents::put($destination, $json);

        return $destination;
    }
}
