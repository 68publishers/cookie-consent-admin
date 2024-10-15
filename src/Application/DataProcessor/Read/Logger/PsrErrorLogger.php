<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Read\Logger;

use App\Application\DataProcessor\Read\Event\ReaderErrorEvent;
use Psr\Log\LoggerInterface;

final readonly class PsrErrorLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ReaderErrorEvent $event): void
    {
        $this->logger->error($event->error()->getMessage());
    }
}
