<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Write\Destination;

class FileDestination implements DestinationInterface
{
    public const string OPTION_CHMOD = 'chmod';

    private string $filename;

    private array $options = [
        self::OPTION_CHMOD => 0666,
    ];

    private function __construct() {}

    public static function create(string $filename, array $options = []): self
    {
        $destination = new self();
        $destination->filename = $filename;
        $destination->options = $options;

        return $destination;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function __toString(): string
    {
        return sprintf(
            'FILE(%s)',
            $this->filename(),
        );
    }
}
