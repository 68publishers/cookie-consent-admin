<?php

declare(strict_types=1);

namespace App\Infrastructure\Consent;

use App\Domain\Consent\CheckUserIdentifierNotExistsInterface;
use App\Domain\Consent\Exception\UserIdentifierExistsException;
use App\Domain\Consent\ValueObject\UserIdentifier;
use App\Domain\Project\ValueObject\ProjectId;
use App\ReadModel\Consent\ConsentView;
use App\ReadModel\Consent\GetConsentByProjectIdAndUserIdentifierQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckUserIdentifierNotExists implements CheckUserIdentifierNotExistsInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(UserIdentifier $userIdentifier, ProjectId $projectId): void
    {
        $consentView = $this->queryBus->dispatch(GetConsentByProjectIdAndUserIdentifierQuery::create($projectId->toString(), $userIdentifier->value()));

        if ($consentView instanceof ConsentView) {
            throw UserIdentifierExistsException::create($consentView->id, $userIdentifier);
        }
    }
}
