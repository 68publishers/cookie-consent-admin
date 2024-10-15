<?php

declare(strict_types=1);

namespace App\Infrastructure\Import\Doctrine\ReadModel;

use App\Domain\Import\Import;
use App\ReadModel\Import\GetImportByIdQuery;
use App\ReadModel\Import\ImportView;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class GetImportByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(GetImportByIdQuery $query): ?ImportView
    {
        $data = $this->em->createQueryBuilder()
            ->select('i')
            ->from(Import::class, 'i')
            ->where('i.id = :id')
            ->setParameter('id', $query->id())
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return null !== $data ? $this->viewFactory->create(ImportView::class, DoctrineViewData::create($data)) : null;
    }
}
