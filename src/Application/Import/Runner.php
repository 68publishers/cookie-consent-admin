<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Application\DataProcessor\AbstractDescribedObject;
use App\Application\DataProcessor\Exception\ReaderException;
use App\Application\DataProcessor\Exception\RowValidationException;
use App\Application\DataProcessor\Read\Event\ReaderErrorEvent;
use App\Application\DataProcessor\Read\Reader\ReaderInterface;
use App\Application\DataProcessor\RowInterface;
use App\Application\Import\Logger\ComposedLogger;
use App\Application\Import\Logger\ImportLogger;
use App\Domain\Import\Command\CompleteImportCommand;
use App\Domain\Import\Command\FailImportCommand;
use App\Domain\Import\Command\StartImportCommand;
use App\Domain\Import\ValueObject\ImportId;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use Spatie\Async\Pool;
use Throwable;

final readonly class Runner implements RunnerInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ImporterInterface $importer,
        private LoggerInterface $logger,
    ) {}

    public function run(ReaderInterface $reader, ImportOptions $options): ImportState
    {
        if (!is_subclass_of($options->describedObjectClassname(), AbstractDescribedObject::class)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s must be extended from %s.',
                $options->describedObjectClassname(),
                AbstractDescribedObject::class,
            ));
        }

        $state = new ImportState(ImportId::new()->toString());
        $options = $options->withLogger(new ComposedLogger(new ImportLogger($state), $options->logger()));

        $this->commandBus->dispatch(StartImportCommand::create($state->id, $options->describedObjectClassname(), $options->authorId()));

        try {
            $state = $this->doRun($reader, $options, $state);

            if ($state::STATUS_COMPLETED === $state->status) {
                $this->commandBus->dispatch(CompleteImportCommand::create($state->id, $state->importedTotal(), $state->failedTotal(), $state->warningsTotal, $state->output));
            } else {
                $this->commandBus->dispatch(FailImportCommand::create($state->id, $state->importedTotal(), $state->failedTotal(), $state->warningsTotal, $state->output));
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage()); // log into "global" logger (e.g. Sentry)
            $options->logger()->error($e->getMessage()); // log into output

            $state->status = $state::STATUS_FAILED;

            $this->commandBus->dispatch(FailImportCommand::create($state->id, $state->importedTotal(), $state->failedTotal(), $state->warningsTotal, $state->output));
        }

        return $state;
    }

    private function doRun(ReaderInterface $reader, ImportOptions $options, ImportState $state): ImportState
    {
        $describedObjectClassname = $options->describedObjectClassname();
        $logger = $options->logger();
        $batchSize = $options->batchSize();

        $onError = function (ReaderErrorEvent $event) use ($state, $logger): void {
            $error = $event->error();
            $logger->error($error->getMessage());

            if ($error instanceof ReaderException) {
                $state->status = $state::STATUS_FAILED;
                $event->stop();

                return;
            }

            if ($error instanceof RowValidationException) {
                $state->addFailed($error->rowIndex());
            }
        };

        $pool = (new Pool())
            ->withBinary(defined('PHP_BINARY') && !empty(PHP_BINARY) ? PHP_BINARY : 'php');

        if (!$options->async()) {
            $pool = $pool->forceSynchronous();
        }

        $batch = [];

        foreach ($reader->read(([$describedObjectClassname, 'describe'])(), $onError) as $row) {
            $batch[] = $row;

            if ($batchSize === count($batch)) {
                $batchForImport = $batch;
                $batch = [];

                $this->doImport($pool, $batchForImport, $state, $logger);
            }
        }

        if (0 < count($batch)) {
            $this->doImport($pool, $batch, $state, $logger);
        }

        $pool->wait();
        $state->resolveStatus();

        return $state;
    }

    /**
     * @param array<RowInterface> $rows
     */
    private function doImport(Pool $pool, array $rows, ImportState $state, LoggerInterface $logger): void
    {
        $pool->add(
            new ImportTask($rows, $this->importer),
        )->then(function (ImporterResult $importerResult) use ($state, $logger): void {
            $this->processImporterResult($importerResult, $state, $logger);
        })->catch(function (Throwable $e) use ($rows, $state, $logger): void {
            $this->processImporterFailed($rows, $e, $state, $logger);
        })->timeout(function () use ($rows, $state, $logger): void {
            $this->processImporterTimeout($rows, $state, $logger);
        });
    }

    private function processImporterResult(ImporterResult $importerResult, ImportState $state, LoggerInterface $logger): void
    {
        $importerResult->each(function (RowResult $rowResult) use ($state, $logger) {
            $message = sprintf(
                '[:%s] %s',
                $rowResult->rowIndex(),
                $rowResult->message(),
            );

            if ($rowResult->ok()) {
                $state->addImported($rowResult->rowIndex());
                $logger->info($message);
            } else {
                $state->addFailed($rowResult->rowIndex());
                $logger->error($message);
            }

            foreach ($rowResult->warnings() as $warning) {
                $state->warningsTotal++;

                $logger->warning(sprintf(
                    '[:%s] %s',
                    $rowResult->rowIndex(),
                    $warning,
                ));
            }
        });
    }

    /**
     * @param array<RowInterface> $rows
     */
    private function processImporterFailed(array $rows, Throwable $e, ImportState $state, LoggerInterface $logger): void
    {
        foreach ($rows as $row) {
            $state->addFailed($row->index());
            $logger->error(sprintf(
                '[:%s] %s',
                $row->index(),
                $e->getMessage(),
            ));
        }
    }

    /**
     * @param array<RowInterface> $rows
     */
    private function processImporterTimeout(array $rows, ImportState $state, LoggerInterface $logger): void
    {
        foreach ($rows as $row) {
            $state->addFailed($row->index());
            $logger->error(sprintf(
                '[:%s] Import took too long and ended with a timeout.',
                $row->index(),
            ));
        }
    }
}
