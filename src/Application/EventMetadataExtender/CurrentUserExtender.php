<?php

declare(strict_types=1);

namespace App\Application\EventMetadataExtender;

use Nette\Security\User as NetteUser;
use SixtyEightPublishers\ArchitectureBundle\Domain\Event\AbstractDomainEvent;
use SixtyEightPublishers\ArchitectureBundle\EventStore\EventMetadataExtenderInterface;

final class CurrentUserExtender implements EventMetadataExtenderInterface
{
    public const string KEY_USER_ID = 'user_id';

    public function __construct(
        private readonly NetteUser $user,
    ) {}

    public function extendMetadata(AbstractDomainEvent $event): AbstractDomainEvent
    {
        if ($this->user->isLoggedIn()) {
            $event = $event->withMetadata([
                self::KEY_USER_ID => (string) $this->user->id,
            ], true);
        }

        return $event;
    }
}
