<?php

declare(strict_types=1);

namespace App\Infrastructure\Project;

use App\Application\Cookie\CompileException;
use App\Application\Cookie\Template as RenderableTemplate;
use App\Application\Cookie\TemplateArguments;
use App\Application\Cookie\TemplateRendererInterface;
use App\Domain\Project\Exception\InvalidTemplateException;
use App\Domain\Project\TemplateValidatorInterface;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\Template;
use App\Domain\Shared\ValueObject\Locale;

final readonly class TemplateValidator implements TemplateValidatorInterface
{
    public function __construct(
        private TemplateRendererInterface $cookieTemplateRenderer,
    ) {}

    public function __invoke(ProjectId $projectId, Template $template, Locale $locale): void
    {
        $tpl = RenderableTemplate::create(
            $projectId->toString(),
            $template->value(),
            TemplateArguments::create([], []),
        );

        $tpl = $tpl->withOption(RenderableTemplate::OPTION_NO_CACHE, true);

        try {
            $this->cookieTemplateRenderer->render($tpl);
        } catch (CompileException $e) {
            throw InvalidTemplateException::fromPrevious($projectId, $locale, $e);
        }
    }
}
