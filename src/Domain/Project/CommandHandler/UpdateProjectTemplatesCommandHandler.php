<?php

declare(strict_types=1);

namespace App\Domain\Project\CommandHandler;

use App\Domain\Project\Command\UpdateProjectTemplatesCommand;
use App\Domain\Project\ProjectRepositoryInterface;
use App\Domain\Project\TemplateValidatorInterface;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\Template;
use App\Domain\Shared\ValueObject\Locale;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class UpdateProjectTemplatesCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private TemplateValidatorInterface $templateValidator,
    ) {}

    public function __invoke(UpdateProjectTemplatesCommand $command): void
    {
        $project = $this->projectRepository->get(ProjectId::fromString($command->projectId()));

        foreach ($command->templates() as $locale => $template) {
            $project->changeTemplate(Locale::fromValue($locale), Template::fromValue($template), $this->templateValidator);
        }

        $this->projectRepository->save($project);
    }
}
