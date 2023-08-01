<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieSuggestion\Doctrine\ReadModel;

use App\ReadModel\CookieSuggestion\CookieSuggestion;
use App\ReadModel\CookieSuggestion\GetCookieSuggestionByProjectIdAndNameAndDomainQuery;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final class GetCookieSuggestionByProjectIdAndNameAndDomainQueryHandler implements QueryHandlerInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @throws Exception
     */
    public function __invoke(GetCookieSuggestionByProjectIdAndNameAndDomainQuery $query): ?CookieSuggestion
    {
        $connection = $this->em->getConnection();

        $result = $connection->createQueryBuilder()
            ->select('cs.id, cs.project_id, cs.name, cs.domain, cs.created_at')
            ->from('cookie_suggestion', 'cs')
            ->where('cs.project_id = :projectId')
            ->andWhere('cs.name = :name')
            ->andWhere('cs.domain = :domain')
            ->setParameters([
                'projectId' => $query->projectId(),
                'name' => $query->name(),
                'domain' => $query->domain(),
            ])
            ->setMaxResults(1)
            ->fetchAssociative();

        return $result ? new CookieSuggestion(
            $result['id'],
            $result['project_id'],
            $result['name'],
            $result['domain'],
            new DateTimeImmutable($result['created_at']),
        ) : null;
    }
}
