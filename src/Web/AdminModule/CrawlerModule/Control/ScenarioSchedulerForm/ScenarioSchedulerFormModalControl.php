<?php

declare(strict_types=1);

namespace App\Web\AdminModule\CrawlerModule\Control\ScenarioSchedulerForm;

use Closure;
use App\Web\Ui\Modal\AbstractModalControl;
use Nette\Application\BadRequestException;
use App\Application\Crawler\CrawlerClientProvider;
use App\Application\Crawler\CrawlerNotConfiguredException;
use App\Web\AdminModule\CrawlerModule\Control\GetScenarioSchedulerResponseTrait;

final class ScenarioSchedulerFormModalControl extends AbstractModalControl
{
	use GetScenarioSchedulerResponseTrait;

	private CrawlerClientProvider $crawlerClientProvider;

	private ScenarioSchedulerFormControlFactoryInterface $runScenarioFormControlFactory;

	private ?string $scenarioSchedulerId;

	private ?Closure $innerControlCreationCallback = NULL;

	public function __construct(
		CrawlerClientProvider $crawlerClientProvider,
		ScenarioSchedulerFormControlFactoryInterface $runScenarioFormControlFactory,
		?string $scenarioSchedulerId
	) {
		$this->crawlerClientProvider = $crawlerClientProvider;
		$this->runScenarioFormControlFactory = $runScenarioFormControlFactory;
		$this->scenarioSchedulerId = $scenarioSchedulerId;
	}

	public function setInnerControlCreationCallback(?Closure $innerControlCreationCallback): void
	{
		$this->innerControlCreationCallback = $innerControlCreationCallback;
	}

	/**
	 * @throws CrawlerNotConfiguredException
	 */
	protected function beforeRender(): void
	{
		parent::beforeRender();

		$template = $this->getTemplate();
		assert($template instanceof ScenarioSchedulerFormModalTemplate);

		$template->scenarioSchedulerId = $this->scenarioSchedulerId;
		$template->scenarioSchedulerResponse = $this->getScenarioSchedulerResponse($this->crawlerClientProvider->get(), $this->scenarioSchedulerId);
		$template->responseError = $this->responseError;
	}

	/**
	 * @throws BadRequestException
	 * @throws CrawlerNotConfiguredException
	 */
	protected function createComponentScenarioSchedulerForm(): ScenarioSchedulerFormControl
	{
		$response = NULL;

		if (NULL !== $this->scenarioSchedulerId) {
			$response = $this->getScenarioSchedulerResponse($this->crawlerClientProvider->get(), $this->scenarioSchedulerId);

			if (NULL === $response) {
				$this->error(sprintf(
					'Unable to fetch response for scenario scheduler %s. %s',
					$this->scenarioSchedulerId,
					NULL !== $this->responseError ? (string) $this->responseError : '',
				));
			}
		}

		$control = $this->runScenarioFormControlFactory->create($response);

		if (NULL !== $this->innerControlCreationCallback) {
			($this->innerControlCreationCallback)($control);
		}

		return $control;
	}
}
