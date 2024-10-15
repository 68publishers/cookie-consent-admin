<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieProvider\Doctrine\ReadModel;

use App\Domain\CookieProvider\CookieProvider;
use App\ReadModel\CookieProvider\CookieProviderView;
use App\ReadModel\CookieProvider\FindCookieProvidersByCodesQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class FindCookieProvidersByCodesQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @return array<CookieProviderView>
     */
    public function __invoke(FindCookieProvidersByCodesQuery $query): array
    {
        $data = $this->em->createQueryBuilder()
            ->select('c, ct')
            ->from(CookieProvider::class, 'c')
            ->leftJoin('c.translations', 'ct')
            ->where('LOWER(c.code) IN (:codes)')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('codes', array_map('strtolower', $query->codes()))
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_map(fn (array $item): CookieProviderView => $this->viewFactory->create(CookieProviderView::class, DoctrineViewData::create($item)), $data);
    }
}
