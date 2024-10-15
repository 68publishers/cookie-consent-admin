<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieProvider\Doctrine\ReadModel;

use App\Domain\CookieProvider\CookieProvider;
use App\ReadModel\CookieProvider\CookieProviderView;
use App\ReadModel\CookieProvider\GetCookieProviderByCodeQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class GetCookieProviderByCodeQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(GetCookieProviderByCodeQuery $query): ?CookieProviderView
    {
        $data = $this->em->createQueryBuilder()
            ->select('c, ct')
            ->from(CookieProvider::class, 'c')
            ->leftJoin('c.translations', 'ct')
            ->where('LOWER(c.code) = LOWER(:code)')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('code', $query->code())
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return null !== $data ? $this->viewFactory->create(CookieProviderView::class, DoctrineViewData::create($data)) : null;
    }
}
