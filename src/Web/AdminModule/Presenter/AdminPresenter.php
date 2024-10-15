<?php

declare(strict_types=1);

namespace App\Web\AdminModule\Presenter;

use App\Application\GlobalSettings\GlobalSettingsInterface;
use App\Application\GlobalSettings\ValidLocalesProvider;
use App\Application\Localization\ApplicationDateTimeZone;
use App\ReadModel\User\UserView;
use App\Web\Control\Footer\FooterControl;
use App\Web\Control\Footer\FooterControlFactoryInterface;
use App\Web\Ui\Presenter;
use Contributte\MenuControl\UI\MenuComponent;
use Contributte\MenuControl\UI\MenuComponentFactory;
use Nette\Application\AbortException;
use Nette\HtmlStringable;
use SixtyEightPublishers\SmartNetteComponent\Attribute\LoggedIn;
use SixtyEightPublishers\SmartNetteComponent\Exception\ForbiddenRequestException;
use SixtyEightPublishers\UserBundle\Application\Exception\IdentityException;
use SixtyEightPublishers\UserBundle\Bridge\Nette\Security\Identity;

#[LoggedIn]
abstract class AdminPresenter extends Presenter
{
    private const string MENU_NAME_SIDEBAR = 'sidebar';
    private const string MENU_NAME_PROFILE = 'profile';

    protected GlobalSettingsInterface $globalSettings;

    protected ValidLocalesProvider $validLocalesProvider;

    private MenuComponentFactory $menuComponentFactory;

    protected array $customBreadcrumbItems = [];

    private FooterControlFactoryInterface $footerControlFactory;

    public function injectAdminDependencies(GlobalSettingsInterface $globalSettings, ValidLocalesProvider $validLocalesProvider, MenuComponentFactory $menuComponentFactory, FooterControlFactoryInterface $footerControlFactory): void
    {
        $this->globalSettings = $globalSettings;
        $this->validLocalesProvider = $validLocalesProvider;
        $this->menuComponentFactory = $menuComponentFactory;
        $this->footerControlFactory = $footerControlFactory;
    }

    /**
     * @throws AbortException
     */
    protected function onForbiddenRequest(ForbiddenRequestException $exception): void
    {
        if ($exception->rule instanceof LoggedIn) {
            $this->redirect(':Front:SignIn:', [
                'backLink' => $this->storeRequest(),
            ]);
        }
    }

    /**
     * @throws IdentityException
     */
    protected function startup(): void
    {
        parent::startup();

        $userView = $this->getIdentity()->data();
        assert($userView instanceof UserView);

        ApplicationDateTimeZone::set($userView->timezone);
    }

    /**
     * @throws IdentityException
     */
    protected function beforeRender(): void
    {
        parent::beforeRender();

        $template = $this->getTemplate();
        assert($template instanceof AdminTemplate);

        $userView = $this->getIdentity()->data();
        assert($userView instanceof UserView);

        $template->identity = $userView;
        $template->locales = $this->validLocalesProvider->getValidLocales();
        $template->defaultLocale = $this->validLocalesProvider->getValidDefaultLocale();
    }

    protected function addBreadcrumbItem(HtmlStringable|string $title): void
    {
        $this->customBreadcrumbItems[] = $title;
    }

    protected function setBreadcrumbItems(array $items): void
    {
        $this->customBreadcrumbItems = $items;
    }

    protected function getIdentity(): Identity
    {
        $identity = $this->getUser()->getIdentity();
        assert($identity instanceof Identity);

        return $identity;
    }

    protected function redrawSidebar(): void
    {
        $this->redrawControl('sidebar-menu-mobile');
        $this->redrawControl('sidebar-menu-desktop');
    }

    protected function createComponentSidebarMenu(): MenuComponent
    {
        $control = $this->menuComponentFactory->create(self::MENU_NAME_SIDEBAR);

        $control->onAnchor[] = function (MenuComponent $component) {
            $component->template->customBreadcrumbItems = $this->customBreadcrumbItems;
        };

        return $control;
    }

    protected function createComponentProfileMenu(): MenuComponent
    {
        return $this->menuComponentFactory->create(self::MENU_NAME_PROFILE);
    }

    protected function createComponentFooter(): FooterControl
    {
        return $this->footerControlFactory->create();
    }
}
