<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieSuggestion\Doctrine\ReadModel;

use App\ReadModel\CookieSuggestion\CookieOccurrenceForResolving;
use App\ReadModel\CookieSuggestion\CookieSuggestionForResolving;
use App\ReadModel\CookieSuggestion\FindCookieSuggestionsForResolvingQuery;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class FindCookieSuggestionsForResolvingQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @return array<CookieSuggestionForResolving>
     * @throws Exception
     */
    public function __invoke(FindCookieSuggestionsForResolvingQuery $query): array
    {
        $connection = $this->em->getConnection();
        
        $result = [];
        $rows = $connection->createQueryBuilder()
            ->select('cs.id, cs.name, cs.domain, cs.created_at, cs.ignored_until_next_occurrence, cs.ignored_permanently')
            ->addSelect('JSON_AGG(JSON_BUILD_OBJECT(
                \'id\', oc.id,
                \'scenario_name\', oc.scenario_name,
                \'found_on_url\', oc.found_on_url,
                \'accepted_categories\', oc.accepted_categories,
                \'last_found_at\', oc.last_found_at
            ) ORDER BY oc.last_found_at DESC) AS occurrences')
            ->from('cookie_suggestion', 'cs')
            ->leftJoin('cs', 'cookie_occurrence', 'oc', 'cs.id = oc.cookie_suggestion_id')
            ->where('cs.project_id = :projectId')
            ->groupBy('cs.id')
            ->setParameters([
                'projectId' => $query->projectId(),
            ])
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $occurrences = [];

            foreach (json_decode($row['occurrences'], true, 512, JSON_THROW_ON_ERROR) as $occurrenceRow) {
                if (null === ($occurrenceRow['id'] ?? null)) {
                    continue;
                }

                $occurrences[] = new CookieOccurrenceForResolving(
                    $occurrenceRow['id'],
                    $occurrenceRow['scenario_name'],
                    $row['name'],
                    $occurrenceRow['found_on_url'],
                    $occurrenceRow['accepted_categories'],
                    new DateTimeImmutable($occurrenceRow['last_found_at']),
                );
            }

            $result[] = new CookieSuggestionForResolving(
                $row['id'],
                $row['name'],
                $row['domain'],
                new DateTimeImmutable($row['created_at']),
                $row['ignored_until_next_occurrence'],
                $row['ignored_permanently'],
                $occurrences,
            );
        }

        return $result;
    }
}
