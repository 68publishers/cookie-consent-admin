<?php

declare(strict_types=1);

namespace App\Infrastructure\Consent\Doctrine\ReadModel;

use App\ReadModel\Consent\CalculateLastConsentDateQuery;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class CalculateLastConsentDateQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(CalculateLastConsentDateQuery $query): ?DateTimeImmutable
    {
        $qb = $this->em->getConnection()->createQueryBuilder()
            ->select('MAX(sp.created_at) AS last_consent_date')
            ->from('consent_statistics_projection', 'sp')
            ->where('sp.project_id = :projectId AND sp.created_at <= :maxDate')
            ->setParameters(
                params: [
                    'projectId' => $query->projectId(),
                    'maxDate' => $query->maxDate(),
                ],
                types: [
                    'projectId' => Types::GUID,
                    'maxDate' => Types::DATETIME_IMMUTABLE,
                ],
            );

        if (null !== $query->environment()) {
            $qb->andWhere('sp.environment = :environment')
                ->setParameter('environment', $query->environment());
        }

        $result = $qb->fetchOne();

        return $result ? new DateTimeImmutable($result, new DateTimeZone('UTC')) : null;
    }
}
