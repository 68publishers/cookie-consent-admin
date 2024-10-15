<?php

declare(strict_types=1);

namespace App\Domain\Category\CommandHandler;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryRepositoryInterface;
use App\Domain\Category\CheckCodeUniquenessInterface;
use App\Domain\Category\Command\CreateCategoryCommand;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class CreateCategoryCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private CheckCodeUniquenessInterface $checkCodeUniqueness,
    ) {}

    public function __invoke(CreateCategoryCommand $command): void
    {
        $category = Category::create($command, $this->checkCodeUniqueness);

        $this->categoryRepository->save($category);
    }
}
