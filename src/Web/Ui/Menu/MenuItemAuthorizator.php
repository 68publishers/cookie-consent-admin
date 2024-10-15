<?php

declare(strict_types=1);

namespace App\Web\Ui\Menu;

use App\Application\GlobalSettings\GlobalSettingsInterface;
use App\Web\AdminModule\CookieModule\Presenter\FoundCookiesPresenter;
use App\Web\AdminModule\CookieModule\Presenter\FoundCookiesProjectsPresenter;
use App\Web\AdminModule\CrawlerModule\Presenter\ScenarioSchedulersPresenter;
use App\Web\AdminModule\CrawlerModule\Presenter\ScenariosPresenter;
use Contributte\MenuControl\IMenuItem;
use Contributte\MenuControl\Security\IAuthorizator;
use Nette\Application\IPresenterFactory;
use Nette\Application\PresenterFactory;
use SixtyEightPublishers\SmartNetteComponent\Authorization\ComponentAuthorizatorInterface;
use SixtyEightPublishers\SmartNetteComponent\Exception\ForbiddenRequestException;

final class MenuItemAuthorizator implements IAuthorizator
{
    private const array CRAWLER_BASED_PRESENTERS = [
        ScenariosPresenter::class,
        ScenarioSchedulersPresenter::class,
        FoundCookiesPresenter::class,
        FoundCookiesProjectsPresenter::class,
    ];

    public function __construct(
        private readonly ComponentAuthorizatorInterface $componentAuthorizator,
        private readonly IPresenterFactory $presenterFactory,
        private readonly GlobalSettingsInterface $globalSettings,
    ) {}

    public function isMenuItemAllowed(IMenuItem $item): bool
    {
        if (empty($item->getActionTarget())) {
            return $this->checkChildren($item);
        }

        $target = rtrim($item->getActionTarget());

        if (str_ends_with($target, ':')) {
            $presenter = trim($target, ':');
            $action = 'default';
        } elseif (str_contains($target, ':')) {
            $explode = explode(':', $target);
            $action = array_pop($explode);
            $presenter = trim(implode(':', $explode), ':');
        } else {
            return true;
        }

        assert($this->presenterFactory instanceof PresenterFactory);
        $presenter = $this->presenterFactory->formatPresenterClass($presenter);

        if (!$this->globalSettings->crawlerSettings()->enabled() && in_array($presenter, self::CRAWLER_BASED_PRESENTERS, true)) {
            return false;
        }

        try {
            $this->componentAuthorizator->checkPresenter($presenter);
            $this->componentAuthorizator->checkAction($presenter, $action);

            return true;
        } catch (ForbiddenRequestException $e) {
            return false;
        }
    }

    protected function checkChildren(IMenuItem $item): bool
    {
        foreach ($item->getItems() as $child) {
            if (true === $child->isAllowed()) {
                return true;
            }
        }

        return false;
    }
}
