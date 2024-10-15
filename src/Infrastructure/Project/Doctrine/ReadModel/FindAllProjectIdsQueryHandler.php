<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\ReadModel\Project\FindAllProjectIdsQuery;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class FindAllProjectIdsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @return array<string>
     * @throws Exception
     */
    public function __invoke(FindAllProjectIdsQuery $query): array
    {
        $row = $this->em->getConnection()->createQueryBuilder()
            ->select('p.id')
            ->from('project', 'p')
            ->where('p.deleted_at IS NULL')
            ->fetchAllAssociative();

        return array_values(
            array_map(
                static fn (array $row): string => $row['id'],
                $row,
            ),
        );
    }
}
