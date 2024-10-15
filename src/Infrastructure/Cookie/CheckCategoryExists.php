<?php

declare(strict_types=1);

namespace App\Infrastructure\Cookie;

use App\Domain\Category\Exception\CategoryNotFoundException;
use App\Domain\Category\ValueObject\CategoryId;
use App\Domain\Cookie\CheckCategoryExistsInterface;
use App\ReadModel\Category\CategoryView;
use App\ReadModel\Category\GetCategoryByIdQuery;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class CheckCategoryExists implements CheckCategoryExistsInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(CategoryId $categoryId): void
    {
        $category = $this->queryBus->dispatch(GetCategoryByIdQuery::create($categoryId->toString()));

        if (!$category instanceof CategoryView) {
            throw CategoryNotFoundException::withId($categoryId);
        }
    }
}
