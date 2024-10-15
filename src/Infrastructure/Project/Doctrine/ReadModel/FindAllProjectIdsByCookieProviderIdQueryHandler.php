<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\ReadModel\Project\FindAllProjectIdsByCookieProviderIdQuery;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class FindAllProjectIdsByCookieProviderIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @return array<string>
     * @throws Exception
     */
    public function __invoke(FindAllProjectIdsByCookieProviderIdQuery $query): array
    {
        $row = $this->em->getConnection()->createQueryBuilder()
            ->select('p.id')
            ->from('project', 'p')
            ->leftJoin('p', 'project_has_cookie_provider', 'p_has_cp', 'p_has_cp.project_id = p.id AND p_has_cp.cookie_provider_id = :cookieProviderId')
            ->where('p.deleted_at IS NULL')
            ->andWhere('p.cookie_provider_id = :cookieProviderId OR p_has_cp.id IS NOT NULL')
            ->setParameter('cookieProviderId', $query->cookieProviderId(), Types::GUID)
            ->fetchAllAssociative();

        return array_values(
            array_map(
                static fn (array $row): string => $row['id'],
                $row,
            ),
        );
    }
}
