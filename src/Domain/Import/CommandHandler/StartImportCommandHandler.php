<?php

declare(strict_types=1);

namespace App\Domain\Import\CommandHandler;

use App\Domain\Import\Command\StartImportCommand;
use App\Domain\Import\Import;
use App\Domain\Import\ImportRepositoryInterface;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class StartImportCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ImportRepositoryInterface $importRepository,
    ) {}

    public function __invoke(StartImportCommand $command): void
    {
        $import = Import::create($command);

        $this->importRepository->save($import);
    }
}
