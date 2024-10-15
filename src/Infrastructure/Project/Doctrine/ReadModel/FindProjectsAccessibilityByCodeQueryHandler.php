<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\Domain\Project\Project;
use App\Domain\User\UserHasProject;
use App\ReadModel\Project\FindProjectsAccessibilityByCodeQuery;
use App\ReadModel\Project\ProjectPermissionView;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class FindProjectsAccessibilityByCodeQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @return array<ProjectPermissionView>
     */
    public function __invoke(FindProjectsAccessibilityByCodeQuery $query): array
    {
        $accessibilitySubQuery = $this->em->createQueryBuilder()
            ->select('1')
            ->from(UserHasProject::class, 'uhp')
            ->where('uhp.projectId = p.id AND uhp.user = :userId')
            ->getQuery()
            ->getDQL();

        $data = $this->em->createQueryBuilder()
            ->select('p.id AS projectId, p.code AS projectCode')
            ->addSelect(sprintf(
                'CASE WHEN (%s) = 1 THEN true ELSE false END AS permission',
                $accessibilitySubQuery,
            ))
            ->from(Project::class, 'p')
            ->where('p.deletedAt IS NULL AND p.code IN (:projectCodes)')
            ->orderBy('p.createdAt', 'DESC')
            ->setParameters([
                'userId' => $query->userId(),
                'projectCodes' => $query->projectCodes(),
            ])
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_map(fn (array $row): ProjectPermissionView => $this->viewFactory->create(ProjectPermissionView::class, DoctrineViewData::create($row)), $data);
    }
}
