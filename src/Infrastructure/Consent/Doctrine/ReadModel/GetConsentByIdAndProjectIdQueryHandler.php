<?php

declare(strict_types=1);

namespace App\Infrastructure\Consent\Doctrine\ReadModel;

use App\Domain\Consent\Consent;
use App\Domain\Project\Project;
use App\ReadModel\Consent\ConsentView;
use App\ReadModel\Consent\GetConsentByIdAndProjectIdQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final class GetConsentByIdAndProjectIdQueryHandler implements QueryHandlerInterface
{
    private EntityManagerInterface $em;

    private ViewFactoryInterface $viewFactory;

    public function __construct(EntityManagerInterface $em, ViewFactoryInterface $viewFactory)
    {
        $this->em = $em;
        $this->viewFactory = $viewFactory;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(GetConsentByIdAndProjectIdQuery $query): ?ConsentView
    {
        $data = $this->em->createQueryBuilder()
            ->select('c')
            ->from(Consent::class, 'c')
            ->join(Project::class, 'p', Join::WITH, 'c.projectId = p.id AND p.id = :projectId AND p.deletedAt IS NULL')
            ->where('c.id = :id')
            ->setParameters([
                'id' => $query->id(),
                'projectId' => $query->projectId(),
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return null !== $data ? $this->viewFactory->create(ConsentView::class, DoctrineViewData::create($data)) : null;
    }
}
