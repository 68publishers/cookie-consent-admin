<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieSuggestion\Doctrine\ReadModel;

use Exception;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use App\ReadModel\CookieSuggestion\CookieOccurrenceForResolving;
use App\ReadModel\CookieSuggestion\CookieSuggestionForResolving;
use App\ReadModel\CookieSuggestion\FindCookieSuggestionsForResolvingQuery;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final class FindCookieSuggestionsForResolvingQueryHandler implements QueryHandlerInterface
{
	private EntityManagerInterface $em;
	
	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	/**
	 * @return array<CookieSuggestionForResolving>
	 * @throws Exception
	 */
	public function __invoke(FindCookieSuggestionsForResolvingQuery $query): array
	{
		$connection = $this->em->getConnection();
		
		$result = [];
		$rows = $connection->createQueryBuilder()
			->select('cs.id, cs.name, cs.domain, cs.ignored_until_next_occurrence')
			->addSelect('JSON_AGG(JSON_BUILD_OBJECT(
                \'id\', oc.id,
                \'scenario_name\', oc.scenario_name,
                \'found_on_url\', oc.found_on_url,
                \'accepted_categories\', oc.accepted_categories,
                \'last_found_at\', oc.last_found_at
            )) AS occurrences')
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

			foreach (json_decode($row['occurrences'], TRUE, 512, JSON_THROW_ON_ERROR) as $occurrenceRow) {
				$occurrences[] = new CookieOccurrenceForResolving(
					$occurrenceRow['id'],
					$occurrenceRow['scenario_name'],
					$occurrenceRow['found_on_url'],
					$occurrenceRow['accepted_categories'],
					new DateTimeImmutable($occurrenceRow['last_found_at']),
				);
			}

			$result[] = new CookieSuggestionForResolving(
				$row['id'],
				$row['name'],
				$row['domain'],
				$row['ignored_until_next_occurrence'],
				$occurrences,
			);
		}

		return $result;
	}
}