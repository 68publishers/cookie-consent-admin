<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\Domain\Cookie\Cookie;
use App\Domain\CookieProvider\CookieProvider;
use App\Domain\GlobalSettings\ValueObject\EnvironmentSettings;
use App\Domain\Project\Project;
use App\Domain\Project\ValueObject\Environment;
use App\Domain\Project\ValueObject\Environments;
use App\ReadModel\Project\CalculateProjectCookieTotalsQuery;
use App\ReadModel\Project\ProjectCookieTotalsView;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class CalculateProjectCookieTotalsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function __invoke(CalculateProjectCookieTotalsQuery $query): ProjectCookieTotalsView
    {
        $environmentCodes = null !== $query->environment() ? [$query->environment()] : null;

        if (null === $environmentCodes) {
            $environments = $this->em->createQueryBuilder()
                ->select('p.environments')
                ->from(Project::class, 'p')
                ->where('p.id = :projectId')
                ->setParameters([
                    'projectId' => $query->projectId(),
                ])
                ->getQuery()
                ->getSingleResult(AbstractQuery::HYDRATE_ARRAY)['environments'];

            assert($environments instanceof Environments);

            $environmentCodes = array_merge(
                [
                    EnvironmentSettings::DEFAULT_ENVIRONMENT_CODE,
                ],
                array_map(
                    static fn (Environment $environment): string => $environment->value(),
                    $environments->all(),
                ),
            );
        }

        $qb = $this->em->createQueryBuilder();
        $cookieEnvironmentCondition = $this->createEnvironmentCondition($environmentCodes, 'c', $qb);
        $cookiePrivateEnvironmentCondition = $this->createEnvironmentCondition($environmentCodes, 'c_private', $qb);

        $qb->select('COUNT(DISTINCT cp.id) AS providers, COUNT(DISTINCT c.id) AS commonCookies, COUNT(DISTINCT c_private.id) AS privateCookies')
            ->from(Project::class, 'p')
            ->leftJoin('p.cookieProviders', 'phcp')
            ->leftJoin(CookieProvider::class, 'cp', Join::WITH, 'cp.id = phcp.cookieProviderId AND cp.createdAt <= :maxDate AND (cp.deletedAt IS NULL OR cp.deletedAt > :maxDate)')
            ->leftJoin(Cookie::class, 'c', Join::WITH, 'c.cookieProviderId = cp.id AND c.createdAt <= :maxDate AND (c.deletedAt IS NULL OR c.deletedAt > :maxDate)' . (null === $cookieEnvironmentCondition ? '' : ('AND (' . $cookieEnvironmentCondition . ')')))
            ->leftJoin(CookieProvider::class, 'cp_private', Join::WITH, 'cp_private.id = p.cookieProviderId AND cp_private.createdAt <= :maxDate AND (cp_private.deletedAt IS NULL OR cp_private.deletedAt > :maxDate)')
            ->leftJoin(Cookie::class, 'c_private', Join::WITH, 'c_private.cookieProviderId = cp_private.id AND c_private.createdAt <= :maxDate AND (c_private.deletedAt IS NULL OR c_private.deletedAt > :maxDate)' . (null === $cookiePrivateEnvironmentCondition ? '' : ('AND (' . $cookiePrivateEnvironmentCondition . ')')))
            ->where('p.id = :projectId')
            ->setParameter('projectId', $query->projectId())
            ->setParameter('maxDate', $query->maxDate());

        $data = $qb->getQuery()->getSingleResult(AbstractQuery::HYDRATE_ARRAY);

        return $this->viewFactory->create(ProjectCookieTotalsView::class, DoctrineViewData::create($data));
    }

    /**
     * @param array<string> $environmentCodes
     */
    private function createEnvironmentCondition(array $environmentCodes, string $tableAlias, QueryBuilder $qb): ?Orx
    {
        if (0 >= count($environmentCodes)) {
            return null;
        }

        $conditions = [
            $tableAlias . '.allEnvironments = true',
        ];

        foreach ($environmentCodes as $index => $code) {
            $conditions[] = sprintf(
                'JSONB_CONTAINS(%s.environments, :environment_%s) = true',
                $tableAlias,
                $index,
            );

            $qb->setParameter('environment_' . $index, json_encode($code));
        }

        return new Orx($conditions);
    }
}
