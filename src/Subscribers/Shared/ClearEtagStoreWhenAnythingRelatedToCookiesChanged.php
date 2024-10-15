<?php

declare(strict_types=1);

namespace App\Subscribers\Shared;

use App\Api\Cache\EtagStoreInterface;
use App\Domain\Category\Category;
use App\Domain\Category\Event\CategoryActiveStateChanged;
use App\Domain\Category\Event\CategoryCodeChanged;
use App\Domain\Category\Event\CategoryNameUpdated;
use App\Domain\Cookie\Cookie;
use App\Domain\Cookie\Event\CookieActiveStateChanged;
use App\Domain\Cookie\Event\CookieCategoryChanged;
use App\Domain\Cookie\Event\CookieCreated;
use App\Domain\Cookie\Event\CookieEnvironmentsChanged;
use App\Domain\Cookie\Event\CookieNameChanged;
use App\Domain\Cookie\Event\CookieProcessingTimeChanged;
use App\Domain\Cookie\Event\CookiePurposeChanged;
use App\Domain\CookieProvider\CookieProvider;
use App\Domain\CookieProvider\Event\CookieProviderActiveStateChanged;
use App\Domain\CookieProvider\Event\CookieProviderCodeChanged;
use App\Domain\CookieProvider\Event\CookieProviderLinkChanged;
use App\Domain\CookieProvider\Event\CookieProviderNameChanged;
use App\Domain\CookieProvider\Event\CookieProviderPurposeChanged;
use App\Domain\CookieProvider\Event\CookieProviderTypeChanged;
use App\Domain\GlobalSettings\Event\ApiCacheSettingsChanged;
use App\Domain\GlobalSettings\Event\EnvironmentSettingsChanged;
use App\Domain\Project\Event\ProjectActiveStateChanged;
use App\Domain\Project\Event\ProjectCodeChanged;
use App\Domain\Project\Event\ProjectCookieProviderAdded;
use App\Domain\Project\Event\ProjectCookieProviderRemoved;
use App\Domain\Project\Event\ProjectEnvironmentsChanged;
use App\Domain\Project\Event\ProjectLocalesChanged;
use App\Domain\Project\Event\ProjectTemplateChanged;
use App\Domain\Project\Project;
use SixtyEightPublishers\ArchitectureBundle\Domain\Event\AggregateDeleted;
use SixtyEightPublishers\ArchitectureBundle\Event\EventHandlerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

final readonly class ClearEtagStoreWhenAnythingRelatedToCookiesChanged implements EventHandlerInterface, MessageSubscriberInterface
{
    public function __construct(
        private EtagStoreInterface $etagStore,
    ) {}

    public static function getHandledMessages(): iterable
    {
        # cookie creation & updates
        yield CookieCreated::class;
        yield CookieCategoryChanged::class;
        yield CookieActiveStateChanged::class;
        yield CookieNameChanged::class;
        yield CookieProcessingTimeChanged::class;
        yield CookiePurposeChanged::class;
        yield CookieEnvironmentsChanged::class;

        # cookie provider updates
        yield CookieProviderActiveStateChanged::class;
        yield CookieProviderCodeChanged::class;
        yield CookieProviderLinkChanged::class;
        yield CookieProviderNameChanged::class;
        yield CookieProviderPurposeChanged::class;
        yield CookieProviderTypeChanged::class;

        # category updates
        yield CategoryActiveStateChanged::class;
        yield CategoryCodeChanged::class;
        yield CategoryNameUpdated::class;

        # project changes
        yield ProjectActiveStateChanged::class;
        yield ProjectCodeChanged::class;
        yield ProjectCookieProviderAdded::class;
        yield ProjectCookieProviderRemoved::class;
        yield ProjectLocalesChanged::class;
        yield ProjectTemplateChanged::class;
        yield ProjectEnvironmentsChanged::class;

        # global settings changes
        yield ApiCacheSettingsChanged::class;
        yield EnvironmentSettingsChanged::class;

        # deletes
        yield AggregateDeleted::class => [
            'method' => 'whenAggregateDeleted',
        ];
    }

    public function __invoke(): void
    {
        $this->etagStore->clear();
    }

    public function whenAggregateDeleted(AggregateDeleted $event): void
    {
        $classnames = [
            Cookie::class,
            CookieProvider::class,
            Category::class,
            Project::class,
        ];

        foreach ($classnames as $classname) {
            if (is_a($classname, $event->aggregateClassname(), true)) {
                $this->etagStore->clear();

                break;
            }
        }
    }
}
