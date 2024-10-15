<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\Domain\CookieProvider\CookieProvider;
use App\Domain\Project\Project;
use App\ReadModel\Project\FindAllProjectsWithPossibleAssociationWithCookieProviderQuery;
use App\ReadModel\Project\ProjectPermissionView;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class FindAllProjectsWithPossibleAssociationWithCookieProviderQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @return array<ProjectPermissionView>
     */
    public function __invoke(FindAllProjectsWithPossibleAssociationWithCookieProviderQuery $query): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p.id AS projectId, p.code AS projectCode')
            ->addSelect('CASE WHEN (cp_private.id IS NULL AND cp.id IS NULL) THEN false ELSE true END AS permission')
            ->from(Project::class, 'p')
            ->leftJoin(CookieProvider::class, 'cp_private', Join::WITH, 'cp_private.id = p.cookieProviderId AND cp_private.id = :cookieProviderId AND cp_private.deletedAt IS NULL')
            ->leftJoin('p.cookieProviders', 'phcp', Join::WITH, 'phcp.cookieProviderId = :cookieProviderId')
            ->leftJoin(CookieProvider::class, 'cp', Join::WITH, 'cp.id = phcp.cookieProviderId AND cp.deletedAt IS NULL')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->setParameters([
                'cookieProviderId' => $query->cookieProviderId(),
            ]);

        if (null !== $query->projectCodes()) {
            $qb->andWhere('p.code IN (:projectCodes)')
                ->setParameter('projectCodes', $query->projectCodes());
        }

        $data = $qb->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_map(fn (array $row): ProjectPermissionView => $this->viewFactory->create(ProjectPermissionView::class, DoctrineViewData::create($row)), $data);
    }
}
