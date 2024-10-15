<?php

declare(strict_types=1);

namespace App\Domain\Import\CommandHandler;

use App\Domain\Import\Command\FailImportCommand;
use App\Domain\Import\ImportRepositoryInterface;
use App\Domain\Import\ValueObject\ImportId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class FailImportCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ImportRepositoryInterface $importRepository,
    ) {}

    public function __invoke(FailImportCommand $command): void
    {
        $import = $this->importRepository->get(ImportId::fromString($command->id()));

        $import->fail($command);
        $this->importRepository->save($import);
    }
}
