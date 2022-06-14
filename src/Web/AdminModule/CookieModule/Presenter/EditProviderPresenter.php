<?php

declare(strict_types=1);

namespace App\Web\AdminModule\CookieModule\Presenter;

use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\AdminModule\Presenter\AdminPresenter;
use App\ReadModel\CookieProvider\CookieProviderView;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use App\ReadModel\CookieProvider\GetCookieProviderByIdQuery;
use SixtyEightPublishers\FlashMessageBundle\Domain\FlashMessage;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use App\Web\AdminModule\CookieModule\Control\CookieList\CookieListControl;
use App\Web\AdminModule\CookieModule\Control\ProviderForm\ProviderFormControl;
use App\Web\AdminModule\CookieModule\Control\CookieForm\CookieFormModalControl;
use App\Web\AdminModule\CookieModule\Control\CookieForm\Event\CookieCreatedEvent;
use App\Web\AdminModule\CookieModule\Control\ProviderForm\Event\ProviderUpdatedEvent;
use App\Web\AdminModule\CookieModule\Control\CookieList\CookieListControlFactoryInterface;
use App\Web\AdminModule\CookieModule\Control\CookieForm\Event\CookieFormProcessingFailedEvent;
use App\Web\AdminModule\CookieModule\Control\ProviderForm\ProviderFormControlFactoryInterface;
use App\Web\AdminModule\CookieModule\Control\CookieForm\CookieFormModalControlFactoryInterface;
use App\Web\AdminModule\CookieModule\Control\ProviderForm\Event\ProviderFormProcessingFailedEvent;

final class EditProviderPresenter extends AdminPresenter
{
	private ProviderFormControlFactoryInterface $providerFormControlFactory;

	private CookieListControlFactoryInterface $cookieListControlFactory;

	private CookieFormModalControlFactoryInterface $cookieFormModalControlFactory;

	private QueryBusInterface $queryBus;

	private CookieProviderView $cookieProviderView;

	/**
	 * @param \App\Web\AdminModule\CookieModule\Control\ProviderForm\ProviderFormControlFactoryInterface  $providerFormControlFactory
	 * @param \App\Web\AdminModule\CookieModule\Control\CookieList\CookieListControlFactoryInterface      $cookieListControlFactory
	 * @param \App\Web\AdminModule\CookieModule\Control\CookieForm\CookieFormModalControlFactoryInterface $cookieFormModalControlFactory
	 * @param \SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface                              $queryBus
	 */
	public function __construct(ProviderFormControlFactoryInterface $providerFormControlFactory, CookieListControlFactoryInterface $cookieListControlFactory, CookieFormModalControlFactoryInterface $cookieFormModalControlFactory, QueryBusInterface $queryBus)
	{
		parent::__construct();

		$this->providerFormControlFactory = $providerFormControlFactory;
		$this->cookieListControlFactory = $cookieListControlFactory;
		$this->cookieFormModalControlFactory = $cookieFormModalControlFactory;
		$this->queryBus = $queryBus;
	}

	/**
	 * @param string $id
	 *
	 * @return void
	 * @throws \Nette\Application\AbortException
	 */
	public function actionDefault(string $id): void
	{
		$cookieProviderView = CookieProviderId::isValid($id) ? $this->queryBus->dispatch(GetCookieProviderByIdQuery::create($id)) : NULL;

		if (!$cookieProviderView instanceof CookieProviderView || NULL !== $cookieProviderView->deletedAt) {
			$this->subscribeFlashMessage(FlashMessage::warning('provider_not_found'));
			$this->redirect('Providers:');
		}

		$this->cookieProviderView = $cookieProviderView;

		$this->setBreadcrumbItems([
			$this->getPrefixedTranslator()->translate('page_title'),
			$this->cookieProviderView->code->value(),
		]);
	}

	/**
	 * @return \App\Web\AdminModule\CookieModule\Control\ProviderForm\ProviderFormControl
	 */
	protected function createComponentProviderForm(): ProviderFormControl
	{
		$control = $this->providerFormControlFactory->create($this->cookieProviderView);

		$control->setFormFactoryOptions([
			FormFactoryInterface::OPTION_AJAX => TRUE,
		]);

		$control->addEventListener(ProviderUpdatedEvent::class, function (ProviderUpdatedEvent $event) {
			$this->subscribeFlashMessage(FlashMessage::success('provider_updated'));

			$this->setBreadcrumbItems([
				$this->getPrefixedTranslator()->translate('page_title'),
				$event->newCode(),
			]);

			$this->redrawControl('heading');
		});

		$control->addEventListener(ProviderFormProcessingFailedEvent::class, function () {
			$this->subscribeFlashMessage(FlashMessage::error('provider_update_failed'));
		});

		return $control;
	}

	/**
	 * @return \App\Web\AdminModule\CookieModule\Control\CookieList\CookieListControl
	 */
	protected function createComponentCookieList(): CookieListControl
	{
		return $this->cookieListControlFactory->create($this->cookieProviderView->id, $this->validLocalesProvider->getValidDefaultLocale());
	}

	/**
	 * @return \App\Web\AdminModule\CookieModule\Control\CookieForm\CookieFormModalControl
	 */
	protected function createComponentCookieModal(): CookieFormModalControl
	{
		$control = $this->cookieFormModalControlFactory->create($this->cookieProviderView->id);
		$inner = $control->getInnerControl();

		$inner->setFormFactoryOptions([
			FormFactoryInterface::OPTION_AJAX => TRUE,
		]);

		$inner->addEventListener(CookieCreatedEvent::class, function () {
			$this->subscribeFlashMessage(FlashMessage::success('cookie_created'));
			$this->redrawControl('cookie_list');
			$this->closeModal();
		});

		$inner->addEventListener(CookieFormProcessingFailedEvent::class, function () {
			$this->subscribeFlashMessage(FlashMessage::error('cookie_creation_failed'));
		});

		return $control;
	}
}