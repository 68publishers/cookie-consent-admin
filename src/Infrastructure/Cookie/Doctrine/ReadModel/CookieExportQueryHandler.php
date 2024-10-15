<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie\Doctrine\ReadModel;

use App\Domain\Category\Category;
use App\Domain\Cookie\Cookie;
use App\Domain\Cookie\ValueObject\Environment;
use App\Domain\CookieProvider\CookieProvider;
use App\ReadModel\Cookie\CookieExportQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\Batch;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\BatchUtils;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class CookieExportQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @return iterable<Batch>
     */
    public function __invoke(CookieExportQuery $query): iterable
    {
        $q = $this->em->createQueryBuilder()
            ->select('c AS cookie, cat.code AS category, cp.code AS provider')
            ->addSelect('ct AS translations')
            ->from(Cookie::class, 'c')
            ->join(Category::class, 'cat', Join::WITH, 'cat.id = c.categoryId AND cat.deletedAt IS NULL')
            ->join(CookieProvider::class, 'cp', Join::WITH, 'cp.id = c.cookieProviderId AND cp.deletedAt IS NULL')
            ->leftJoin('c.translations', 'ct')
            ->where('c.deletedAt IS NULL')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = new Paginator($q, true);
        $totalCount = count($paginator);

        foreach (BatchUtils::from($totalCount, $query->batchSize()) as [$limit, $offset]) {
            $paginator->getQuery()
                ->setMaxResults($limit)
                ->setFirstResult($query->staticOffset() ?? $offset);

            $results = [];

            foreach ($paginator as $item) {
                $results[] = $this->normalize($item);
            }

            yield Batch::create(
                $query->batchSize(),
                $query->staticOffset() ?? $offset,
                $totalCount,
                $results,
            );
        }
    }

    private function normalize(array $row): array
    {
        $item = [
            'name' => $row['cookie']['name'],
            'domain' => $row['cookie']['domain'],
            'category' => $row['category'],
            'provider' => $row['provider'],
            'processingTime' => $row['cookie']['processingTime'],
            'active' => $row['cookie']['active'],
            'purpose' => [],
            'environments' => $row['cookie']['allEnvironments']
                ? []
                : array_map(
                    static fn (Environment $environment): string => $environment->value(),
                    $row['cookie']['environments']->all(),
                ),
        ];

        foreach ($row['cookie']['translations'] as $translation) {
            $item['purpose'][$translation['locale']->value()] = $translation['purpose'];
        }

        return $item;
    }
}
