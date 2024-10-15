<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie\Doctrine\ReadModel;

use App\Domain\Category\Category;
use App\Domain\Cookie\Cookie;
use App\Domain\CookieProvider\CookieProvider;
use App\ReadModel\Cookie\CookieView;
use App\ReadModel\Cookie\GetCookieByNameAndCookieProviderAndCategoryQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class GetCookieByNameAndCookieProviderAndCategoryQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(GetCookieByNameAndCookieProviderAndCategoryQuery $query): ?CookieView
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c, ct')
            ->from(Cookie::class, 'c')
            ->join(CookieProvider::class, 'cp', Join::WITH, 'cp.id = c.cookieProviderId AND cp.id = :cookieProviderId AND cp.deletedAt IS NULL')
            ->join(Category::class, 'cat', Join::WITH, 'cat.id = c.categoryId AND cat.id = :categoryId AND cat.deletedAt IS NULL')
            ->leftJoin('c.translations', 'ct')
            ->where('c.deletedAt IS NULL')
            ->andWhere('c.name = :name')
            ->setParameters([
                'name' => $query->name(),
                'cookieProviderId' => $query->cookieProviderId(),
                'categoryId' => $query->categoryId(),
            ]);

        $data = $qb->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return null !== $data ? $this->viewFactory->create(CookieView::class, DoctrineViewData::create($data)) : null;
    }
}
