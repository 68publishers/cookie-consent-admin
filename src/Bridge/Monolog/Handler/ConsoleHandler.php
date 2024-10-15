<?php

declare(strict_types=1);

namespace App\Bridge\Monolog\Handler;

use App\Bridge\Monolog\Formatter\ConsoleFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use RuntimeException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConsoleHandler extends AbstractProcessingHandler implements EventSubscriberInterface
{
    /** @var array<int, Level> */
    private array $verbosityLevelMap = [
        OutputInterface::VERBOSITY_QUIET => Level::Error,
        OutputInterface::VERBOSITY_NORMAL => Level::Warning,
        OutputInterface::VERBOSITY_VERBOSE => Level::Notice,
        OutputInterface::VERBOSITY_VERY_VERBOSE => Level::Info,
        OutputInterface::VERBOSITY_DEBUG => Level::Debug,
    ];

    /**
     * @param array<int, Level> $verbosityLevelMap
     */
    public function __construct(
        private ?OutputInterface $output = null,
        bool $bubble = true,
        array $verbosityLevelMap = [],
    ) {
        parent::__construct(Level::Debug, $bubble);

        if ($verbosityLevelMap) {
            $this->verbosityLevelMap = $verbosityLevelMap;
        }
    }

    public function isHandling(LogRecord $record): bool
    {
        return $this->updateLevel() && parent::isHandling($record);
    }

    public function handle(LogRecord $record): bool
    {
        return $this->updateLevel() && parent::handle($record);
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function close(): void
    {
        $this->output = null;

        parent::close();
    }

    /**
     * @internal
     */
    public function onCommand(ConsoleCommandEvent $event): void
    {
        $this->setOutput($event->getOutput());
    }

    /**
     * @internal
     */
    public function onTerminate(): void
    {
        $this->close();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onCommand', 255],
            ConsoleEvents::TERMINATE => ['onTerminate', -255],
        ];
    }

    protected function write(LogRecord $record): void
    {
        $output = $this->output;

        if (null === $output) {
            throw new RuntimeException('Can not write record, no output specified.');
        }

        if ($record->level->value >= Level::Error->value && $output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        $output->write((string) $record->formatted, false, $output->getVerbosity());
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        $formatter = new ConsoleFormatter();

        $formatter->ignoreEmptyContextAndExtra();

        return $formatter;
    }

    private function updateLevel(): bool
    {
        if (null === $this->output) {
            return false;
        }

        $verbosity = $this->output->getVerbosity();
        if (isset($this->verbosityLevelMap[$verbosity])) {
            $this->setLevel($this->verbosityLevelMap[$verbosity]);
        } else {
            $this->setLevel(Level::Debug);
        }

        return true;
    }
}
