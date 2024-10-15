<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie\Doctrine\ReadModel;

use App\Infrastructure\DataGridQueryHandlerTrait;
use App\ReadModel\Cookie\CookieDataGridItem;
use App\ReadModel\Cookie\CookieProjectItem;
use App\ReadModel\Cookie\CookiesDataGridQuery;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Orx;
use JsonException;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final class CookiesDataGridQueryHandler implements QueryHandlerInterface
{
    use DataGridQueryHandlerTrait;

    public const string FILTER_ENVIRONMENTS_ALL = '//all//';

    /**
     * @throws NonUniqueResultException|NoResultException|JsonException|Exception
     */
    public function __invoke(CookiesDataGridQuery $query): array|int
    {
        $connection = $this->em->getConnection();

        return $this->processQuery(
            $query,
            static function (CookiesDataGridQuery $query) use ($connection): QueryBuilder {
                $qb = $connection->createQueryBuilder()
                    ->select('COUNT(c.id)')
                    ->from('cookie', 'c')
                    ->leftJoin('c', 'category', 'cat', 'c.category_id = cat.id AND cat.deleted_at IS NULL')
                    ->join('c', 'cookie_provider', 'cp', 'c.cookie_provider_id = cp.id AND cp.deleted_at IS NULL')
                    ->where('c.deleted_at IS NULL');

                if (null !== $query->cookieProviderId()) {
                    $qb->andWhere('cp.id = :cookieProviderId')
                        ->setParameter('cookieProviderId', $query->cookieProviderId());
                }

                if (null !== $query->projectId()) {
                    $qb->leftJoin('cp', 'project_has_cookie_provider', 'phcp', 'cp.id = phcp.cookie_provider_id')
                        ->leftJoin('phcp', 'project', 'phcp_p', 'phcp.project_id = phcp_p.id AND phcp_p.deleted_at IS NULL');

                    if (false === $query->projectServicesOnly()) {
                        $qb->leftJoin('cp', 'project', 'p', 'cp.id = p.cookie_provider_id AND p.deleted_at IS NULL');
                    }

                    $qb->andWhere($query->projectServicesOnly() ? 'phcp_p.id = :projectId' : 'phcp_p.id = :projectId OR p.id = :projectId')
                        ->setParameter('projectId', $query->projectId());
                }

                return $qb;
            },
            static function (CookiesDataGridQuery $query) use ($connection): QueryBuilder {
                $qb = $connection->createQueryBuilder()
                    ->select('c.id AS id, cat.id AS category_id, c.name AS cookie_name, c.processing_time, c.active, cat_t.name AS category_name, c.created_at, c.all_environments, c.environments')
                    ->addSelect('cp.id AS cookie_provider_id, cp.name AS cookie_provider_name, cp.type AS cookie_provider_type, cp.private AS cookie_provider_private')
                    ->from('cookie', 'c')
                    ->leftJoin('c', 'category', 'cat', 'c.category_id = cat.id AND cat.deleted_at IS NULL')
                    ->leftJoin('cat', 'category_translation', 'cat_t', 'cat.id = cat_t.category_id AND cat_t.locale = :locale')
                    ->join('c', 'cookie_provider', 'cp', 'c.cookie_provider_id = cp.id AND cp.deleted_at IS NULL')
                    ->where('c.deleted_at IS NULL')
                    ->setParameters([
                        'locale' => $query->locale() ?? '_unknown_',
                    ]);

                if (null !== $query->cookieProviderId()) {
                    $qb->andWhere('cp.id = :cookieProviderId')
                        ->setParameter('cookieProviderId', $query->cookieProviderId());
                }

                if (null !== $query->projectId()) {
                    $qb->leftJoin('cp', 'project_has_cookie_provider', 'phcp', 'cp.id = phcp.cookie_provider_id')
                        ->leftJoin('phcp', 'project', 'phcp_p', 'phcp.project_id = phcp_p.id AND phcp_p.deleted_at IS NULL');

                    if (false === $query->projectServicesOnly()) {
                        $qb->leftJoin('cp', 'project', 'p', 'cp.id = p.cookie_provider_id AND p.deleted_at IS NULL');
                    }

                    $qb->andWhere($query->projectServicesOnly() ? 'phcp_p.id = :projectId' : 'phcp_p.id = :projectId OR p.id = :projectId')
                        ->setParameter('projectId', $query->projectId());
                }

                if ($query->includeProjectsData()) {
                    $projectsSubQuery1 = $connection->createQueryBuilder()
                        ->select('DISTINCT __p.code, __p.name, __p.color')
                        ->from('project', '__p')
                        ->leftJoin('__p', 'project_has_cookie_provider', '__phcp', '__p.id = __phcp.project_id')
                        ->where('__p.deleted_at IS NULL')
                        ->andWhere('__p.cookie_provider_id = cp.id OR __phcp.cookie_provider_id = cp.id')
                        ->getSQL();

                    $projectsSubQuery2 = $connection->createQueryBuilder()
                        ->select('json_agg(jsonb_build_object(\'name\', __p2.name, \'color\', __p2.color) ORDER BY __p2.name)')
                        ->from('(' . $projectsSubQuery1 . ')', '__p2');

                    $qb->addSelect(sprintf(
                        '(%s) AS projects',
                        $projectsSubQuery2,
                    ));
                }

                return $qb;
            },
            fn (array $row): CookieDataGridItem => new CookieDataGridItem(
                id: $row['id'],
                cookieName: $row['cookie_name'],
                processingTime: $row['processing_time'],
                active: $row['active'],
                categoryId: $row['category_id'],
                categoryName: $row['category_name'],
                cookieProviderId: $row['cookie_provider_id'],
                cookieProviderName: $row['cookie_provider_name'],
                cookieProviderType: $row['cookie_provider_type'],
                cookieProviderPrivate: $row['cookie_provider_private'],
                createdAt: $connection->convertToPHPValue($row['created_at'], Types::DATETIME_IMMUTABLE),
                projects: $this->mapProjects($row['projects'] ?? null),
                environments: $row['all_environments'] ? true : $connection->convertToPHPValue($row['environments'], Types::JSON),
            ),
            [
                'id' => ['applyEquals', 'c.id'],
                'cookieName' => ['applyLike', 'c.name'],
                'categoryId' => ['applyIn', 'cat.id'],
                'providerId' => ['applyIn', 'cp.id'],
                'providerType' => ['applyEquals', 'cp.type'],
                'createdAt' => ['applyDate', 'c.created_at'],
                'active' => ['applyEquals', 'c.active'],
                'projects' => ['applyProjects', '_'],
                'environments' => ['applyEnvironments', '_'],
            ],
            [
                'cookieName' => 'c.name',
                'categoryName' => 'cat_t.name',
                'providerName' => 'cp.name',
                'createdAt' => 'c.created_at',
            ],
        );
    }

    private function applyProjects(QueryBuilder $qb, string $_, mixed $value): void
    {
        $value = (array) $value;

        if (empty($value)) {
            return;
        }

        $paramName = $this->newParameterName();
        $existsQuery1 = $this->em->getConnection()->createQueryBuilder()
            ->select('1')
            ->from('project_has_cookie_provider', 'project_filter1')
            ->where('project_filter1.cookie_provider_id = cp.id')
            ->andWhere(sprintf('project_filter1.project_id IN (:%s)', $paramName))
            ->getSQL();

        $existsQuery2 = $this->em->getConnection()->createQueryBuilder()
            ->select('1')
            ->from('project', 'project_filter2')
            ->where('project_filter2.cookie_provider_id = cp.id')
            ->andWhere(sprintf('project_filter2.id IN (:%s)', $paramName))
            ->getSQL();

        $qb->andWhere(sprintf(
            '(EXISTS(%s) OR EXISTS(%s))',
            $existsQuery1,
            $existsQuery2,
        ));
        $qb->setParameter($paramName, $value, ArrayParameterType::STRING);
    }

    public function applyEnvironments(DbalQueryBuilder $qb, string $_, mixed $value): void
    {
        $hasAllCondition = false;
        $environmentConditions = [];

        foreach ((array) $value as $v) {
            if (self::FILTER_ENVIRONMENTS_ALL === $v) {
                $hasAllCondition = true;

                continue;
            }

            $p = $this->newParameterName();
            $environmentConditions[] = sprintf(
                'c.environments @> :%s',
                $p,
            );

            $qb->setParameter($p, json_encode($v));
        }

        $mainConditions = $hasAllCondition ? ['c.all_environments = true'] : [];
        $mainConditions = array_merge($mainConditions, $environmentConditions);

        if (0 < count($mainConditions)) {
            $qb->andWhere(new Orx($mainConditions));
        }
    }

    /**
     * @return array<int, CookieProjectItem>
     * @throws JsonException
     */
    private function mapProjects(mixed $projectsJson): array
    {
        if (!is_string($projectsJson)) {
            return [];
        }

        $decoded = json_decode(
            json: $projectsJson,
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
        $projects = [];

        foreach ($decoded as $item) {
            $projects[] = new CookieProjectItem(
                name: $item['name'],
                color: $item['color'],
            );
        }

        return $projects;
    }
}
