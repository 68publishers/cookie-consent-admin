<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Write\Writer;

use App\Application\DataProcessor\Description\ListDescriptor;
use App\Application\DataProcessor\Description\Path\Path;
use App\Application\DataProcessor\Description\Path\PathInfo;
use App\Application\DataProcessor\Helper\FlatResource;
use App\Application\DataProcessor\RowInterface;
use App\Application\DataProcessor\Write\Destination\DestinationInterface;
use App\Application\DataProcessor\Write\Destination\FileDestination;
use App\Application\DataProcessor\Write\Destination\StringDestination;
use App\Application\DataProcessor\Write\Helper\FilePutContents;
use App\Application\DataProcessor\Write\Resource\ResourceInterface;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Writer;

final class CsvWriter extends AbstractWriter
{
    public const string OPTION_DELIMITER = 'delimiter';
    public const string OPTION_ENCLOSURE = 'enclosure';
    public const string OPTION_ESCAPE = 'escape';
    public const string INCLUDE_BOM = 'include_bom';

    private ?Writer $writer = null;

    private array $headers = [];

    private array $rows = [];

    private array $pathInfos = [];

    public static function fromFile(ResourceInterface $resource, FileDestination $destination): self
    {
        return new self($resource, $destination);
    }

    public static function fromString(ResourceInterface $resource, StringDestination $destination): self
    {
        return new self($resource, $destination);
    }

    /**
     * @throws InvalidArgument
     */
    protected function prepare(): void
    {
        $this->writer = $writer = Writer::createFromString();
        $this->headers = $this->rows = $this->pathInfos = [];
        $options = $this->destination->options();

        if (isset($options[self::OPTION_DELIMITER])) {
            $writer->setDelimiter($options[self::OPTION_DELIMITER]);
        }

        if (isset($options[self::OPTION_ENCLOSURE])) {
            $writer->setEnclosure($options[self::OPTION_ENCLOSURE]);
        }

        if (isset($options[self::OPTION_ESCAPE])) {
            $writer->setEscape($options[self::OPTION_ESCAPE]);
        }

        if (isset($options[self::INCLUDE_BOM])) {
            $writer->setOutputBOM($writer::BOM_UTF8);
        }
    }

    protected function processRow(RowInterface $row, DestinationInterface $destination): DestinationInterface
    {
        $flatten = FlatResource::toFlattArray($row->data()->toArray());
        $data = [];

        foreach ($flatten as $k => $v) {
            if (is_array($v)) {
                $v = implode(',', $v);
            } elseif (is_bool($v)) {
                $v = $v ? '1' : '0';
            }

            $pathInfo = $this->getPathInfo($k);

            if (null === $pathInfo) {
                $this->headers[] = $k;
                $data[$k] = $v;

                continue;
            }

            if (!$pathInfo->found) {
                continue;
            }

            if ($pathInfo->isFinal || $pathInfo->descriptor instanceof ListDescriptor) {
                $this->headers[] = $k;
                $data[$k] = $v;
            }
        }

        $this->rows[] = $data;

        return $destination;
    }

    /**
     * @throws Exception
     */
    protected function finish(DestinationInterface $destination): DestinationInterface
    {
        $headers = array_unique($this->headers);
        $defaults = array_fill_keys($headers, '');

        $this->writer->insertOne($headers);

        foreach ($this->rows as $row) {
            $this->writer->insertOne(array_merge($defaults, $row));
        }

        $content = $this->writer->toString();
        $this->writer = null;
        $this->headers = $this->rows = $this->pathInfos = [];

        if ($destination instanceof StringDestination) {
            return $destination->append($content);
        }

        assert($destination instanceof FileDestination);

        FilePutContents::put($destination, $content);

        return $destination;
    }

    private function getPathInfo(string $path): ?PathInfo
    {
        return $this->pathInfos[$path] ?? ($this->pathInfos[$path] = null !== $this->resource->descriptor() ? $this->resource->descriptor()->pathInfo(Path::fromString($path)) : null);
    }
}
