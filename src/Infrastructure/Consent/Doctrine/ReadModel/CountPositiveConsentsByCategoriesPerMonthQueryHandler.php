<?php

declare(strict_types=1);

namespace App\Infrastructure\Consent\Doctrine\ReadModel;

use App\Domain\Consent\Event\ConsentCreated;
use App\Domain\Consent\Event\ConsentUpdated;
use App\ReadModel\Consent\CountConsentsByCategoriesPerMonthQuery;
use App\ReadModel\Consent\MonthlyStatistics;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class CountPositiveConsentsByCategoriesPerMonthQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(CountConsentsByCategoriesPerMonthQuery $query): MonthlyStatistics
    {
        $positiveCategoryFilter = $this->createCategoryFilter($query->acceptedCategories(), true);
        $negativeCategoryFilter = $this->createCategoryFilter($query->rejectedCategories(), false);

        if ($query->unique()) {
            $sql = <<<SQL
            SELECT
              month,
              count(*) AS "count"
            FROM (
              SELECT DISTINCT ON (_es.aggregate_id, date_part('month', _es.created_at)) date_part('month', _es.created_at) AS month
              FROM consent_event_stream _es
              WHERE
                date_part('year', _es.created_at) = :year
                AND _es.event_name IN (:eventNames)
                AND _es.parameters->>'project_id' = :projectId
                {$positiveCategoryFilter}
                {$negativeCategoryFilter}
              ORDER BY _es.aggregate_id, month DESC, _es.created_at DESC
            ) es
            GROUP BY month
            SQL;
        } else {
            $sql = <<<SQL
            SELECT
              date_part('month', _es.created_at) AS "month",
              count(*) AS "count"
            FROM consent_event_stream _es
            WHERE
              date_part('year', _es.created_at) = :year
              AND _es.event_name IN (:eventNames)
              AND _es.parameters->>'project_id' = :projectId
              {$positiveCategoryFilter}
              {$negativeCategoryFilter}
            GROUP BY month;
            SQL;
        }

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addScalarResult('month', 'month', 'integer');
        $rsm->addScalarResult('count', 'count', 'integer');

        $data = $this->em->createNativeQuery($sql, $rsm)
            ->setParameters([
                'projectId' => $query->projectId(),
                'year' => $query->year(),
                'eventNames' => [
                    ConsentCreated::class,
                    ConsentUpdated::class,
                ],
            ])
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return MonthlyStatistics::fromArray(array_column($data, 'count', 'month'));
    }

    /**
     * @param array<int, string> $categories
     */
    private function createCategoryFilter(array $categories, bool $positivity): string
    {
        return implode(
            ' ',
            array_map(
                static fn (string $categoryCode): string => sprintf(
                    "AND (_es.parameters->'consents'->>'%s')::boolean = %s",
                    $categoryCode,
                    $positivity ? 'true' : 'false',
                ),
                $categories,
            ),
        );
    }
}
