<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Read\Reader;

use App\Application\DataProcessor\ArrayRowData;
use App\Application\DataProcessor\Context\Context;
use App\Application\DataProcessor\Context\ContextInterface;
use App\Application\DataProcessor\Exception\ReaderException;
use App\Application\DataProcessor\Helper\FlatResource;
use App\Application\DataProcessor\Read\Resource\FileResource;
use App\Application\DataProcessor\Read\Resource\StringResource;
use App\Application\DataProcessor\Row;
use League\Csv\Exception as LeagueException;
use League\Csv\Info;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\Statement;

final class CsvReader extends AbstractReader
{
    public const string OPTION_DELIMITER = 'delimiter';
    public const string OPTION_ENCLOSURE = 'enclosure';
    public const string OPTION_ESCAPE = 'escape';
    public const string OPTION_HAS_HEADER = 'has_header';

    public static function fromFile(FileResource $resource): self
    {
        return new self($resource);
    }

    public static function fromString(StringResource $resource): self
    {
        return new self($resource);
    }

    protected function doRead(ErrorCallback $errorCallback): iterable
    {
        try {
            $reader = $this->createReader();
            $rows = Statement::create()->process($reader);
        } catch (LeagueException $e) {
            $errorCallback(ReaderException::invalidResource($e->getMessage()));

            return [];
        }

        foreach ($rows as $index => $row) {
            yield Row::create((string) $index, ArrayRowData::create(FlatResource::toMultidimensionalArray($row)));
        }
    }

    protected function createContext(): ContextInterface
    {
        return Context::default([
            ContextInterface::WEAK_TYPES => true,
        ]);
    }

    /**
     * @throws LeagueException
     * @throws InvalidArgument
     */
    private function createReader(): Reader
    {
        $resource = $this->resource;
        $options = $resource->options();

        if ($resource instanceof FileResource) {
            $reader = Reader::createFromPath($resource->filename());
            $encoding = mb_detect_encoding($reader->toString());
        } else {
            assert($resource instanceof StringResource);

            $reader = Reader::createFromString($resource->string());
            $encoding = mb_detect_encoding($resource->string());
        }

        if (is_string($options[self::OPTION_DELIMITER] ?? null)) {
            $reader->setDelimiter($options[self::OPTION_DELIMITER]);
        } else {
            $stats = Info::getDelimiterStats($reader, [',', ';', "\t", '|']);
            arsort($stats);

            $reader->setDelimiter(array_key_first($stats));
        }

        if (is_string($options[self::OPTION_ENCLOSURE] ?? null)) {
            $reader->setEnclosure($options[self::OPTION_ENCLOSURE]);
        }

        if (true === (isset($options[self::OPTION_HAS_HEADER])) ?? false) {
            $reader->setHeaderOffset(0);
        }

        if (true === (isset($options[self::OPTION_ESCAPE])) ?? false) {
            $reader->setEscape($options[self::OPTION_ESCAPE]);
        }

        // probably windows
        if (false === $encoding) {
            $reader->addStreamFilter('convert.iconv.WINDOWS-1250/UTF-8//TRANSLIT//IGNORE');
        }

        return $reader;
    }
}
