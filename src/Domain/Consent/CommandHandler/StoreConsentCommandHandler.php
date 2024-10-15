<?php

declare(strict_types=1);

namespace App\Domain\Consent\CommandHandler;

use App\Domain\Consent\CheckUserIdentifierNotExistsInterface;
use App\Domain\Consent\Command\StoreConsentCommand;
use App\Domain\Consent\Consent;
use App\Domain\Consent\ConsentRepositoryInterface;
use App\Domain\Consent\Exception\UserIdentifierExistsException;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class StoreConsentCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ConsentRepositoryInterface $consentRepository,
        private CheckUserIdentifierNotExistsInterface $checkUserIdentifierNotExists,
    ) {}

    public function __invoke(StoreConsentCommand $command): void
    {
        try {
            $consent = Consent::create($command, $this->checkUserIdentifierNotExists);
        } catch (UserIdentifierExistsException $e) {
            $consent = $this->consentRepository->get($e->consentId());
            $consent->update($command);
        }

        $this->consentRepository->save($consent);
    }
}
