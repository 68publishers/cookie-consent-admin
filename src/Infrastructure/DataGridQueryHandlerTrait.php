<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\ReadModel\DataGridQueryInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\PaginatedResultFactory;

trait DataGridQueryHandlerTrait
{
    protected EntityManagerInterface $em;

    private PaginatedResultFactory $paginatedResultFactory;

    private int $paramsCount = 0;

    public function __construct(EntityManagerInterface $em, PaginatedResultFactory $paginatedResultFactory)
    {
        $this->em = $em;
        $this->paginatedResultFactory = $paginatedResultFactory;
    }

    protected function beforeCountQueryFetch(QueryBuilder|DbalQueryBuilder $qb): QueryBuilder|DbalQueryBuilder
    {
        return $qb;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    protected function processQuery(DataGridQueryInterface $query, callable $countQueryBuilderFactory, callable $dataQueryBuilderFactory, string|callable $viewClassnameOrCallback, array $filterDefinitions, array $sortingDefinitions)
    {
        if ($query::MODE_COUNT === $query->mode()) {
            $qb = $countQueryBuilderFactory($query);
            assert($qb instanceof QueryBuilder || $qb instanceof DbalQueryBuilder);

            $this->applyFilters($query, $qb, $filterDefinitions);

            $this->paramsCount = 0;
            $qb = $this->beforeCountQueryFetch($qb);

            return (int) ($qb instanceof QueryBuilder ? $qb->getQuery()->getSingleScalarResult() : $qb->fetchOne());
        }

        $qb = $dataQueryBuilderFactory($query);
        assert($qb instanceof QueryBuilder || $qb instanceof DbalQueryBuilder);

        $this->applyFilters($query, $qb, $filterDefinitions);

        if ($query::MODE_ONE !== $query->mode()) {
            $this->applySorting($query, $qb, $sortingDefinitions);
        }

        $this->paramsCount = 0;

        if ($qb instanceof DbalQueryBuilder) {
            $qb->setMaxResults($query->limit());

            if (null !== $query->offset()) {
                $qb->setFirstResult($query->offset());
            }

            $results = [];
            assert(is_callable($viewClassnameOrCallback));

            foreach ($qb->fetchAllAssociative() as $row) {
                $results[] = $viewClassnameOrCallback($row);
            }

            return $results;
        }

        assert(is_string($viewClassnameOrCallback));

        return $this->paginatedResultFactory->create($query, $qb->getQuery(), $viewClassnameOrCallback)->results();
    }

    protected function applyFilters(DataGridQueryInterface $query, QueryBuilder|DbalQueryBuilder $qb, array $definitions): void
    {
        foreach ($query->filters() as $filterName => $value) {
            if (!isset($definitions[$filterName])) {
                throw new RuntimeException(sprintf(
                    'Filter "%s" is not supported by %s.',
                    $filterName,
                    static::class,
                ));
            }

            if (null === $value) {
                continue;
            }

            $def = $definitions[$filterName];

            if (!isset($def[2])) {
                $def[2] = [];
            }

            [$method, $column, $extraArgs] = $def;
            $extraArgs = is_array($extraArgs) ? $extraArgs : [$extraArgs];

            if ($query::MODE_ONE === $query->mode()) {
                $this->applyEquals($qb, $column, $value);

                continue;
            }

            $this->{$method}($qb, $column, $value, ...$extraArgs);
        }
    }

    protected function applySorting(DataGridQueryInterface $query, QueryBuilder|DbalQueryBuilder $qb, array $definitions): void
    {
        foreach ($query->sorting() as $name => $direction) {
            if (!isset($definitions[$name])) {
                throw new RuntimeException(sprintf(
                    'Sorting "%s" is not supported by %s.',
                    $name,
                    static::class,
                ));
            }

            foreach ((array) $definitions[$name] as $column) {
                $qb->addOrderBy($column, $direction);
            }
        }
    }

    protected function applyEquals(QueryBuilder|DbalQueryBuilder $qb, string $column, $value): void
    {
        $p = $this->newParameterName();

        $qb->andWhere(sprintf('%s = :%s', $column, $p))
            ->setParameter($p, $value);
    }

    protected function applyLike(QueryBuilder|DbalQueryBuilder $qb, string $column, string $value): void
    {
        $qb->andWhere($qb->expr()->like('LOWER(' . $column . ')', 'LOWER(' . $qb->expr()->literal("%$value%") . ')'));
    }

    /**
     * @throws \Exception
     */
    protected function applyDate(QueryBuilder|DbalQueryBuilder $qb, string $column, $value): void
    {
        if (!$value instanceof DateTimeInterface) {
            $value = new DateTimeImmutable($value, new DateTimeZone('UTC'));
        }

        $from = (clone $value)->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));
        $to = (clone $value)->setTime(23, 59, 59)->setTimezone(new DateTimeZone('UTC'));

        $p1 = $this->newParameterName();
        $p2 = $this->newParameterName();

        $qb->andWhere(sprintf('%s >= :%s AND %s <= :%s', $column, $p1, $column, $p2))
            ->setParameter($p1, $from->format('Y-m-d H:i:s'))
            ->setParameter($p2, $to->format('Y-m-d H:i:s'));
    }

    protected function applyIn(QueryBuilder|DbalQueryBuilder $qb, string $column, mixed $value): void
    {
        $value = (array) $value;
        $p = $this->newParameterName();
        $type = null;

        if ($qb instanceof DbalQueryBuilder) {
            $firstValue = reset($value);
            $type = is_numeric($firstValue) ? ArrayParameterType::INTEGER : ArrayParameterType::STRING;
        }

        $qb->andWhere(sprintf('%s IN (:%s)', $column, $p))
            ->setParameter($p, $value, $type);
    }

    protected function applyJsonbContains(QueryBuilder|DbalQueryBuilder $qb, string $column, $value): void
    {
        $condition = [];

        foreach ((array) $value as $val) {
            $p = $this->newParameterName();
            $condition[] = $qb instanceof DbalQueryBuilder
                ? sprintf('%s @> :%s', $column, $p)
                : sprintf('JSONB_CONTAINS(%s, :%s) = true', $column, $p);

            $qb->setParameter($p, $val, Types::JSON);
        }

        if (0 >= count($condition)) {
            return;
        }

        if (1 === count($condition)) {
            $condition = array_shift($condition);
        } else {
            $condition = $qb instanceof DbalQueryBuilder ? $qb->expr()->or(...$condition) : $qb->expr()->orX(...$condition);
        }

        $qb->andWhere($condition);
    }

    protected function newParameterName(): string
    {
        $return = 'param_' . ($this->paramsCount + 1);
        $this->paramsCount++;

        return $return;
    }
}
