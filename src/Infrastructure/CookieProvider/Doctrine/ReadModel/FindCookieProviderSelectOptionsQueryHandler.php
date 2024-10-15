<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieProvider\Doctrine\ReadModel;

use App\Domain\CookieProvider\CookieProvider;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectHasCookieProvider;
use App\Domain\Project\ValueObject\ProjectId;
use App\ReadModel\CookieProvider\CookieProviderSelectOptionView;
use App\ReadModel\CookieProvider\FindCookieProviderSelectOptionsQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class FindCookieProviderSelectOptionsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @return array<CookieProviderSelectOptionView>
     */
    public function __invoke(FindCookieProviderSelectOptionsQuery $query): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c.id, c.name, c.code, c.private')
            ->from(CookieProvider::class, 'c')
            ->andWhere('c.deletedAt IS NULL')
            ->orderBy('c.name', 'ASC');

        if (null !== $query->assignedProjectId()) {
            $qb->join(ProjectHasCookieProvider::class, 'phc', Join::WITH, 'phc.cookieProviderId = c.id AND phc.project = :assignedProjectId')
                ->join('phc.project', 'p', Join::WITH, 'p.deletedAt IS NULL')
                ->setParameter('assignedProjectId', $query->assignedProjectId());
        }

        $private = $query->private();

        if (false === $private) {
            $qb->andWhere('c.private = false');
        } elseif (is_string($private) && ProjectId::isValid($private)) {
            $qb->leftJoin(Project::class, 'pr', Join::WITH, 'pr.cookieProviderId = c.id AND pr.id = :privateProjectId')
                ->andWhere('(c.private = false OR pr.id IS NOT NULL)')
                ->setParameter('privateProjectId', $private);
        }

        $data = $qb->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_map(fn (array $item): CookieProviderSelectOptionView => $this->viewFactory->create(CookieProviderSelectOptionView::class, DoctrineViewData::create($item)), $data);
    }
}
