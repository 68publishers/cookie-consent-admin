<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\Domain\Project\Project;
use App\Domain\User\UserHasProject;
use App\ReadModel\Project\FindUserProjectsQuery;
use App\ReadModel\Project\ProjectView;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final class FindUserProjectsQueryHandler implements QueryHandlerInterface
{
    private EntityManagerInterface $em;

    private ViewFactoryInterface $viewFactory;

    public function __construct(EntityManagerInterface $em, ViewFactoryInterface $viewFactory)
    {
        $this->em = $em;
        $this->viewFactory = $viewFactory;
    }

    /**
     * @return ProjectView[]
     */
    public function __invoke(FindUserProjectsQuery $query): array
    {
        $data = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Project::class, 'p')
            ->join(UserHasProject::class, 'uhp', Join::WITH, 'uhp.projectId = p.id AND uhp.user = :userId')
            ->where('p.deletedAt IS NULL')
            ->orderBy('p.name', 'ASC')
            ->setParameter('userId', $query->userId())
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_map(fn (array $row): ProjectView => $this->viewFactory->create(ProjectView::class, DoctrineViewData::create($row)), $data);
    }
}
