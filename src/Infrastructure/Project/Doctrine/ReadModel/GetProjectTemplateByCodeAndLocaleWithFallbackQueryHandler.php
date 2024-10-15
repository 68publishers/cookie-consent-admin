<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\Domain\Project\Project;
use App\ReadModel\Project\GetProjectTemplateByCodeAndLocaleWithFallbackQuery;
use App\ReadModel\Project\ProjectTemplateView;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;

final readonly class GetProjectTemplateByCodeAndLocaleWithFallbackQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(GetProjectTemplateByCodeAndLocaleWithFallbackQuery $query): ?ProjectTemplateView
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p.id AS projectId, p.locales.locales, p.locales.defaultLocale, p.environments')
            ->from(Project::class, 'p')
            ->where('LOWER(p.code) = LOWER(:code) AND p.deletedAt IS NULL')
            ->setParameter('code', $query->code());

        if (null !== $query->locale()) {
            $qb->addSelect('COALESCE(NULLIF(pt.template, \'\'), pt_default.template, \'\') AS template')
                ->addSelect('COALESCE(pt.locale, p.locales.defaultLocale) AS templateLocale')
                ->leftJoin('p.translations', 'pt_default', Join::WITH, 'pt_default.locale = p.locales.defaultLocale')
                ->leftJoin('p.translations', 'pt', Join::WITH, 'pt.locale = :locale')
                ->setParameter('locale', $query->locale());
        } else {
            $qb->addSelect('COALESCE(pt_default.template, \'\') AS template')
                ->addSelect('COALESCE(pt_default.locale, p.locales.defaultLocale) AS templateLocale')
                ->leftJoin('p.translations', 'pt_default', Join::WITH, 'pt_default.locale = p.locales.defaultLocale');
        }

        $data = $qb->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return $this->viewFactory->create(ProjectTemplateView::class, DoctrineViewData::create($data));
    }
}
