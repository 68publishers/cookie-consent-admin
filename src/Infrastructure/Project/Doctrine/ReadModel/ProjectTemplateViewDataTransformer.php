<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\Domain\Shared\ValueObject\Locale;
use App\Domain\Project\ValueObject\Template;
use App\ReadModel\Project\ProjectTemplateView;
use App\Domain\Shared\ValueObject\LocalesConfig;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewDataInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewFactoryInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\View\ViewDataTransformerInterface;
use SixtyEightPublishers\ArchitectureBundle\Infrastructure\Doctrine\ReadModel\DoctrineViewData;

final class ProjectTemplateViewDataTransformer implements ViewDataTransformerInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function canTransform(string $viewClassname, ViewDataInterface $viewData): bool
	{
		return is_a($viewClassname, ProjectTemplateView::class, TRUE) && $viewData instanceof DoctrineViewData;
	}

	/**
	 * {@inheritDoc}
	 */
	public function transform(ViewDataInterface $viewData, ViewFactoryInterface $viewFactory): ViewDataInterface
	{
		if ($viewData->has('template') && !$viewData->get('template') instanceof Template) {
			$viewData = $viewData->with('template', Template::fromValue($viewData->get('template')));
		}

		if ($viewData->has('templateLocale') && !$viewData->get('templateLocale') instanceof Locale) {
			$viewData = $viewData->with('templateLocale', Locale::fromValue($viewData->get('templateLocale')));
		}

		return $viewData->with('projectLocalesConfig', LocalesConfig::create($viewData->get('locales.locales'), $viewData->get('locales.defaultLocale')))
			->without('locales.locales', 'locales.defaultLocale');
	}
}
