<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie\Doctrine\ReadModel;

use App\Domain\Cookie\Cookie;
use App\ReadModel\Cookie\CookieView;
use App\ReadModel\Cookie\FindCookiesByCookieProviderQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class FindCookiesByCookieProviderQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @return iterable<CookieView>
     */
    public function __invoke(FindCookiesByCookieProviderQuery $query): iterable
    {
        $data = $this->em->createQueryBuilder()
            ->select('c, ct')
            ->from(Cookie::class, 'c')
            ->leftJoin('c.translations', 'ct')
            ->where('c.cookieProviderId = :cookieProviderId')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('cookieProviderId', $query->cookieProviderId())
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_map(fn (array $item): CookieView => $this->viewFactory->create(CookieView::class, DoctrineViewData::create($item)), $data);
    }
}
