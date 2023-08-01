<?php

declare(strict_types=1);

namespace App\ReadModel\Project;

use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\AbstractQuery;

/**
 * Returns array of ProjectSelectOptionsView
 */
final class FindProjectSelectOptionsQuery extends AbstractQuery
{
    /**
     * @return static
     */
    public static function all(): self
    {
        return self::fromParameters([]);
    }

    /**
     * @return static
     */
    public static function byUser(string $userId): self
    {
        return self::fromParameters([
            'user_id' => $userId,
        ]);
    }

    /**
     * @return static
     */
    public static function byCookieProviderId(string $cookieProviderId): self
    {
        return self::fromParameters([
            'cookie_provider_id' => $cookieProviderId,
        ]);
    }

    public function userId(): ?string
    {
        return $this->getParam('user_id');
    }

    public function cookieProviderId(): ?string
    {
        return $this->getParam('cookie_provider_id');
    }
}
