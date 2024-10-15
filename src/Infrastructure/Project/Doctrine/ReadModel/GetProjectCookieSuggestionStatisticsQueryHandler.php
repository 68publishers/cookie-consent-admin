<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\ReadModel\Project\GetProjectCookieSuggestionStatisticsQuery;
use App\ReadModel\Project\ProjectCookieSuggestionsStatistics;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class GetProjectCookieSuggestionStatisticsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function __invoke(GetProjectCookieSuggestionStatisticsQuery $query): ?ProjectCookieSuggestionsStatistics
    {
        $row = $this->em->getConnection()->createQueryBuilder()
            ->select('ps.missing, ps.unassociated, ps.problematic, ps.unproblematic, ps.ignored, ps.total, ps.total_without_virtual, ps.latest_found_at')
            ->from('project_cookie_suggestion_statistics', 'ps')
            ->where('ps.project_id = :projectId')
            ->setMaxResults(1)
            ->setParameter('projectId', $query->projectId(), Types::GUID)
            ->fetchAssociative();

        if (!$row) {
            return null;
        }

        return new ProjectCookieSuggestionsStatistics(
            $row['missing'],
            $row['unassociated'],
            $row['problematic'],
            $row['unproblematic'],
            $row['ignored'],
            $row['total'],
            $row['total_without_virtual'],
            null !== $row['latest_found_at'] ? new DateTimeImmutable($row['latest_found_at']) : null,
        );
    }
}
