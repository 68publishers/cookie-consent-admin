<?php

declare(strict_types=1);

namespace App\Infrastructure\Category;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryRepositoryInterface;
use App\Domain\Category\Exception\CategoryNotFoundException;
use App\Domain\Category\ValueObject\CategoryId;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AggregateId;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Common\Repository\AggregateRootRepositoryInterface;

final readonly class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private AggregateRootRepositoryInterface $aggregateRootRepository,
    ) {}

    public function save(Category $category): void
    {
        $this->aggregateRootRepository->saveAggregateRoot($category);
    }

    public function get(CategoryId $id): Category
    {
        $category = $this->aggregateRootRepository->loadAggregateRoot(Category::class, AggregateId::fromUuid($id->id()));

        if (!$category instanceof Category) {
            throw CategoryNotFoundException::withId($id);
        }

        return $category;
    }
}
