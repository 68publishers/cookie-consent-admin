<?php

declare(strict_types=1);

namespace App\Domain\Category\CommandHandler;

use App\Domain\Category\CategoryRepositoryInterface;
use App\Domain\Category\CheckCodeUniquenessInterface;
use App\Domain\Category\Command\UpdateCategoryCommand;
use App\Domain\Category\ValueObject\CategoryId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class UpdateCategoryCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private CheckCodeUniquenessInterface $checkCodeUniqueness,
    ) {}

    public function __invoke(UpdateCategoryCommand $command): void
    {
        $category = $this->categoryRepository->get(CategoryId::fromString($command->categoryId()));

        $category->update($command, $this->checkCodeUniqueness);

        $this->categoryRepository->save($category);
    }
}
