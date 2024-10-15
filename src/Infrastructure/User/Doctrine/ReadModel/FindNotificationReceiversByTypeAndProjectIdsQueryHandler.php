<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Doctrine\ReadModel;

use App\Domain\Project\Project;
use App\Domain\User\User;
use App\ReadModel\User\FindNotificationReceiversByTypeAndProjectIdsQuery;
use App\ReadModel\User\NotificationReceiverView;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use JsonException;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\BatchGeneratorFactory;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\Batch;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class FindNotificationReceiversByTypeAndProjectIdsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private BatchGeneratorFactory $batchGeneratorFactory,
    ) {}

    /**
     * @return iterable<Batch>
     * @throws JsonException
     */
    public function __invoke(FindNotificationReceiversByTypeAndProjectIdsQuery $query): iterable
    {
        $qb = $this->em->createQueryBuilder()
            ->select('u.emailAddress, u.name.firstname, u.name.surname, u.profileLocale, JSON_AGG(DISTINCT p.id) AS projectIds')
            ->from(User::class, 'u')
            ->join('u.projects', 'uhp', Join::WITH)
            ->join(Project::class, 'p', Join::WITH, 'p.id = uhp.projectId AND p.deletedAt IS NULL')
            ->where('u.deletedAt IS NULL')
            ->andWhere('JSONB_CONTAINS(u.notificationPreferences, :notificationType) = true')
            ->setParameters([
                'notificationType' => json_encode($query->notificationType(), JSON_THROW_ON_ERROR),
            ])
            ->groupBy('u.emailAddress', 'u.name.firstname', 'u.name.surname', 'u.profileLocale');

        if (!empty($query->projectIdsOnly())) {
            $qb->andWhere('p.id IN (:projectIds)')
                ->setParameter('projectIds', $query->projectIdsOnly());
        }

        return $this->batchGeneratorFactory->create($query, $qb->getQuery(), NotificationReceiverView::class, false, false);
    }
}
