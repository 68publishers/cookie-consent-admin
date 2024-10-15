<?php

declare(strict_types=1);

namespace App\Application\Import\Helper;

use App\Application\Cookie\Import\CookieData;
use App\Application\CookieProvider\Import\CookieProviderData;
use App\Application\Project\Import\ProjectData;

/**
 * @todo: Replace in the future...
 */
final class KnownDescriptors
{
    public const array ALL = [
        CookieProviderData::class,
        CookieData::class,
        ProjectData::class,
    ];

    private function __construct() {}
}
