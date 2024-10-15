<?php

declare(strict_types=1);

namespace App\Infrastructure\Consent\Doctrine\ReadModel;

use App\ReadModel\Consent\CountFullyNegativeConsentsPerMonthQuery;
use App\ReadModel\Consent\MonthlyStatistics;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class CountFullyNegativeConsentsPerMonthQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(CountFullyNegativeConsentsPerMonthQuery $query): MonthlyStatistics
    {
        if ($query->unique()) {
            $sql = <<<SQL
            SELECT
              month,
              count(*) AS "count"
            FROM (
              SELECT DISTINCT ON (_sp.consent_id, date_part('month', _sp.created_at)) date_part('month', _sp.created_at) as month
              FROM consent_statistics_projection _sp
              WHERE
                _sp.project_id = :projectId
                AND date_part('year', _sp.created_at) = :year
                AND _sp.positive_count = 0
                AND _sp.negative_count > 0
              ORDER BY _sp.consent_id, month DESC, _sp.created_at DESC
            ) sp
            GROUP BY month
            SQL;
        } else {
            $sql = <<<SQL
            SELECT
              date_part('month', sp.created_at) AS "month",
              count(*) AS "count"
            FROM consent_statistics_projection sp
              WHERE
                sp.project_id = :projectId
                AND date_part('year', sp.created_at) = :year
                AND sp.positive_count = 0
                AND sp.negative_count > 0
            GROUP BY month
            SQL;
        }

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addScalarResult('month', 'month', 'integer');
        $rsm->addScalarResult('count', 'count', 'integer');

        $data = $this->em->createNativeQuery($sql, $rsm)
            ->setParameters([
                'projectId' => $query->projectId(),
                'year' => $query->year(),
            ])
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return MonthlyStatistics::fromArray(array_column($data, 'count', 'month'));
    }
}
