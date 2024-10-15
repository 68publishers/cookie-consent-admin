<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Doctrine\ReadModel;

use App\ReadModel\User\ExternalAuthView;
use App\ReadModel\User\FindExternalAuthenticationsQuery;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Exception;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class FindExternalAuthenticationsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @return array<int, ExternalAuthView>
     * @throws Exception
     */
    public function __invoke(FindExternalAuthenticationsQuery $query): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('uea.user_id, uea.provider_code, uea.created_at, uea.resource_owner_id')
            ->from('user_external_auth', 'uea')
            ->join('uea', '"user"', 'u', 'u.id = uea.user_id AND u.deleted_at IS NULL')
            ->where('uea.user_id = :userId')
            ->orderBy('uea.created_at', 'DESC')
            ->setParameters([
                'userId' => $query->userId(),
            ])
            ->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $result[] = new ExternalAuthView(
                userId: $row['user_id'],
                providerCode: $row['provider_code'],
                createdAt: new DateTimeImmutable($row['created_at'], new DateTimeZone('UTC')),
                resourceOwnerId: $row['resource_owner_id'],
            );
        }

        return $result;
    }
}
