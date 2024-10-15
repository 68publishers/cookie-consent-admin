<?php

declare(strict_types=1);

namespace App\Infrastructure\Consent\Doctrine\ReadModel;

use App\ReadModel\Consent\CalculateConsentStatisticsPerPeriodQuery;
use App\ReadModel\Consent\ConsentStatisticsView;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class CalculateConsentStatisticsPerPeriodQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function __invoke(CalculateConsentStatisticsPerPeriodQuery $query): ConsentStatisticsView
    {
        $environmentParameter = $query->environment();
        $environmentCondition = null !== $environmentParameter ? 'AND sp.environment = :environment' : '';
        $totalStatisticsQuery = <<<SQL
        SELECT
            count(*) AS "totalConsentsCount",
            coalesce(sum(sp.positive_count), 0) AS "totalPositiveCount",
            coalesce(sum(sp.negative_count), 0) AS "totalNegativeCount"
        FROM consent_statistics_projection sp
        WHERE sp.project_id = :projectId $environmentCondition AND sp.created_at BETWEEN :startDate AND :endDate
        SQL;

        $parameters = [
            'projectId' => $query->projectId(),
            'startDate' => $query->startDate(),
            'endDate' => $query->endDate(),
        ];

        if (null !== $environmentParameter) {
            $parameters['environment'] = $environmentParameter;
        }

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addScalarResult('totalConsentsCount', 'totalConsentsCount', 'integer');
        $rsm->addScalarResult('totalPositiveCount', 'totalPositiveCount', 'integer');
        $rsm->addScalarResult('totalNegativeCount', 'totalNegativeCount', 'integer');

        $totalData = $this->em->createNativeQuery($totalStatisticsQuery, $rsm)
            ->setParameters($parameters)
            ->getSingleResult(AbstractQuery::HYDRATE_ARRAY);

        $environmentCondition = null !== $environmentParameter ? 'AND _sp.environment = :environment' : '';
        $uniqueStatisticsQuery = <<<SQL
        SELECT
            count(*) AS "uniqueConsentsCount",
            coalesce(sum(sp.positive_count), 0) AS "uniquePositiveCount",
            coalesce(sum(sp.negative_count), 0) AS "uniqueNegativeCount"
        FROM (
            SELECT DISTINCT ON (_sp.consent_id) _sp.positive_count, _sp.negative_count
            FROM consent_statistics_projection _sp
            WHERE _sp.project_id = :projectId $environmentCondition AND _sp.created_at BETWEEN :startDate AND :endDate
            ORDER BY _sp.consent_id, _sp.created_at DESC
        ) sp
        SQL;

        $parameters = [
            'projectId' => $query->projectId(),
            'startDate' => $query->startDate(),
            'endDate' => $query->endDate(),
        ];

        if (null !== $environmentParameter) {
            $parameters['environment'] = $environmentParameter;
        }

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addScalarResult('uniqueConsentsCount', 'uniqueConsentsCount', 'integer');
        $rsm->addScalarResult('uniquePositiveCount', 'uniquePositiveCount', 'integer');
        $rsm->addScalarResult('uniqueNegativeCount', 'uniqueNegativeCount', 'integer');

        $uniqueData = $this->em->createNativeQuery($uniqueStatisticsQuery, $rsm)
            ->setParameters($parameters)
            ->getSingleResult(AbstractQuery::HYDRATE_ARRAY);

        $data = array_merge($totalData, $uniqueData);

        return $this->viewFactory->create(ConsentStatisticsView::class, DoctrineViewData::create($data));
    }
}
