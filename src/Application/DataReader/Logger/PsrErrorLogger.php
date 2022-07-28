<?php

declare(strict_types=1);

namespace App\Application\DataReader\Logger;

use Psr\Log\LoggerInterface;
use App\Application\DataReader\Event\ReaderErrorEvent;

final class PsrErrorLogger
{
	private LoggerInterface $logger;

	/**
	 * @param \Psr\Log\LoggerInterface $logger
	 */
	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @param \App\Application\DataReader\Event\ReaderErrorEvent $event
	 *
	 * @return void
	 */
	public function __invoke(ReaderErrorEvent $event): void
	{
		$this->logger->error($event->error()->getMessage());
	}
}
