<?php

declare(strict_types=1);

namespace App\Domain\Import\CommandHandler;

use App\Domain\Import\Command\CompleteImportCommand;
use App\Domain\Import\ImportRepositoryInterface;
use App\Domain\Import\ValueObject\ImportId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class CompleteImportCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ImportRepositoryInterface $importRepository,
    ) {}

    public function __invoke(CompleteImportCommand $command): void
    {
        $import = $this->importRepository->get(ImportId::fromString($command->id()));

        $import->complete($command);
        $this->importRepository->save($import);
    }
}
