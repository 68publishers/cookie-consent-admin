<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie\Doctrine\ReadModel;

use App\Domain\Category\Category;
use App\Domain\Cookie\Cookie;
use App\Domain\CookieProvider\CookieProvider;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectHasCookieProvider;
use App\ReadModel\Cookie\CookieApiView;
use App\ReadModel\Cookie\FindCookiesForApiQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Orx;
use Generator;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\BatchGeneratorFactory;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final readonly class FindCookiesForApiQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private BatchGeneratorFactory $batchGeneratorFactory,
    ) {}

    public function __invoke(FindCookiesForApiQuery $query): Generator
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c.name AS cookieName, c.processingTime AS processingTime')
            ->addSelect('cp.code AS cookieProviderCode, cp.name AS cookieProviderName, cp.type AS cookieProviderType, cp.link AS cookieProviderLink')
            ->addSelect('cat.code AS categoryCode')
            ->from(Cookie::class, 'c')
            ->join(Project::class, 'p', Join::WITH, 'p.id = :projectId AND p.deletedAt IS NULL')
            ->join(Category::class, 'cat', Join::WITH, 'cat.id = c.categoryId AND cat.deletedAt IS NULL AND cat.active = true')
            ->join(CookieProvider::class, 'cp', Join::WITH, 'cp.id = c.cookieProviderId AND cp.deletedAt IS NULL AND cp.active = true')
            ->leftJoin(ProjectHasCookieProvider::class, 'phc', Join::WITH, 'phc.cookieProviderId = cp.id AND phc.project = p')
            ->where('c.deletedAt IS NULL')
            ->andWhere('c.active = true')
            ->andWhere('(phc.id IS NOT NULL OR cp.id = p.cookieProviderId)')
            ->orderBy('c.createdAt', 'ASC')
            ->setParameter('projectId', $query->projectId());

        $environmentsConditions = [
            'c.allEnvironments = true',
        ];

        foreach ($query->environments() as $index => $environment) {
            $environmentsConditions[] = "JSONB_CONTAINS(c.environments, :environment_$index) = true";
            $qb->setParameter('environment_' . $index, json_encode([$environment]));
        }

        $qb->andWhere(new Orx($environmentsConditions));

        $qb->leftJoin('c.translations', 'ct_default', Join::WITH, 'ct_default.locale = p.locales.defaultLocale')
            ->leftJoin('cat.translations', 'catt_default', Join::WITH, 'catt_default.locale = p.locales.defaultLocale')
            ->leftJoin('cp.translations', 'cpt_default', Join::WITH, 'cpt_default.locale = p.locales.defaultLocale');

        if (null !== $query->locale()) {
            $qb->addSelect('COALESCE(NULLIF(ct.purpose, \'\'), ct_default.purpose, \'\') AS cookiePurpose')
                ->addSelect('COALESCE(NULLIF(cpt.purpose, \'\'), cpt_default.purpose, \'\') AS cookieProviderPurpose')
                ->addSelect('COALESCE(NULLIF(catt.name, \'\'), catt_default.name, \'\') AS categoryName')
                ->leftJoin('c.translations', 'ct', Join::WITH, 'ct.locale = :locale')
                ->leftJoin('cat.translations', 'catt', Join::WITH, 'catt.locale = :locale')
                ->leftJoin('cp.translations', 'cpt', Join::WITH, 'cpt.locale = :locale')
                ->setParameter('locale', $query->locale());
        } else {
            $qb->addSelect('COALESCE(ct_default.purpose, \'\') AS cookiePurpose')
                ->addSelect('COALESCE(cpt_default.purpose, \'\') AS cookieProviderPurpose')
                ->addSelect('COALESCE(catt_default.name, \'\') AS categoryName');
        }

        if (null !== $query->categoryCodes()) {
            $qb->andWhere('cat.code IN (:categoryCodes)')
                ->setParameter('categoryCodes', $query->categoryCodes());
        }

        return $this->batchGeneratorFactory->create($query, $qb->getQuery(), CookieApiView::class, false, false);
    }
}
