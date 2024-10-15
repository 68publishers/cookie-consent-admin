<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\Domain\Project\Project;
use App\Domain\Project\ValueObject\ProjectId;
use App\ReadModel\Project\ProjectExistsQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class ProjectExistsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @return ProjectId|false
     * @throws NonUniqueResultException
     */
    public function __invoke(ProjectExistsQuery $query): ProjectId|bool
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p.id')
            ->from(Project::class, 'p')
            ->where('p.deletedAt IS NULL');

        if (null !== $query->projectId()) {
            $qb->andWhere('p.id = :projectId')
                ->setParameter('projectId', $query->projectId());
        }

        if (null !== $query->code()) {
            $qb->andWhere('p.code = :code')
                ->setParameter('code', $query->code());
        }

        try {
            return $qb->getQuery()->getSingleResult()['id'];
        } catch (NoResultException $e) {
            return false;
        }
    }
}
