<?php

declare(strict_types=1);

namespace App\Infrastructure\Category;

use App\Domain\Category\CheckCodeUniquenessInterface;
use App\Domain\Category\Exception\CodeUniquenessException;
use App\Domain\Category\ValueObject\CategoryId;
use App\Domain\Category\ValueObject\Code;
use App\ReadModel\Category\CategoryView;
use App\ReadModel\Category\GetCategoryByCodeQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckCodeUniqueness implements CheckCodeUniquenessInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(CategoryId $categoryId, Code $code): void
    {
        $categoryView = $this->queryBus->dispatch(GetCategoryByCodeQuery::create($code->value()));

        if (!$categoryView instanceof CategoryView) {
            return;
        }

        if (!$categoryView->id->equals($categoryId)) {
            throw CodeUniquenessException::create($code->value());
        }
    }
}
