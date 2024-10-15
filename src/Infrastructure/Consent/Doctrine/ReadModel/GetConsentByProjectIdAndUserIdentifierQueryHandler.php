<?php

declare(strict_types=1);

namespace App\Infrastructure\Consent\Doctrine\ReadModel;

use App\Domain\Consent\Consent;
use App\Domain\Project\Project;
use App\ReadModel\Consent\ConsentView;
use App\ReadModel\Consent\GetConsentByProjectIdAndUserIdentifierQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class GetConsentByProjectIdAndUserIdentifierQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(GetConsentByProjectIdAndUserIdentifierQuery $query): ?ConsentView
    {
        $data = $this->em->createQueryBuilder()
            ->select('c')
            ->from(Consent::class, 'c')
            ->join(Project::class, 'p', Join::WITH, 'c.projectId = p.id AND p.id = :projectId AND p.deletedAt IS NULL')
            ->where('c.userIdentifier = :userIdentifier')
            ->setParameters([
                'projectId' => $query->projectId(),
                'userIdentifier' => $query->userIdentifier(),
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return null !== $data ? $this->viewFactory->create(ConsentView::class, DoctrineViewData::create($data)) : null;
    }
}
