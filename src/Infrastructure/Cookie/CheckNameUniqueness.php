<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie;

use App\Domain\Category\ValueObject\CategoryId;
use App\Domain\Cookie\CheckNameUniquenessInterface;
use App\Domain\Cookie\Exception\NameUniquenessException;
use App\Domain\Cookie\ValueObject\CookieId;
use App\Domain\Cookie\ValueObject\Name;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use App\ReadModel\Cookie\CookieView;
use App\ReadModel\Cookie\GetCookieByNameAndCookieProviderAndCategoryQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckNameUniqueness implements CheckNameUniquenessInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(CookieId $cookieId, Name $name, CookieProviderId $cookieProviderId, CategoryId $categoryId): void
    {
        $cookieView = $this->queryBus->dispatch(GetCookieByNameAndCookieProviderAndCategoryQuery::create($name->value(), $cookieProviderId->toString(), $categoryId->toString()));

        if (!$cookieView instanceof CookieView) {
            return;
        }

        if (!$cookieView->id->equals($cookieId)) {
            throw NameUniquenessException::create($name->value(), $cookieProviderId->toString());
        }
    }
}
