<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieProvider;

use App\Domain\CookieProvider\CheckCodeUniquenessInterface;
use App\Domain\CookieProvider\Exception\CodeUniquenessException;
use App\Domain\CookieProvider\ValueObject\Code;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use App\ReadModel\CookieProvider\CookieProviderView;
use App\ReadModel\CookieProvider\GetCookieProviderByCodeQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckCodeUniqueness implements CheckCodeUniquenessInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(CookieProviderId $cookieProviderId, Code $code): void
    {
        $categoryView = $this->queryBus->dispatch(GetCookieProviderByCodeQuery::create($code->value()));

        if (!$categoryView instanceof CookieProviderView) {
            return;
        }

        if (!$categoryView->id->equals($cookieProviderId)) {
            throw CodeUniquenessException::create($code->value());
        }
    }
}
