<?php

declare(strict_types=1);

namespace App\Infrastructure\Category\Doctrine\ReadModel;

use App\Domain\Category\Category;
use App\ReadModel\Category\CategoryView;
use App\ReadModel\Category\GetCategoryByCodeQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class GetCategoryByCodeQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(GetCategoryByCodeQuery $query): ?CategoryView
    {
        $data = $this->em->createQueryBuilder()
            ->select('c, ct')
            ->from(Category::class, 'c')
            ->leftJoin('c.translations', 'ct')
            ->where('LOWER(c.code) = LOWER(:code)')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('code', $query->code())
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return null !== $data ? $this->viewFactory->create(CategoryView::class, DoctrineViewData::create($data)) : null;
    }
}
