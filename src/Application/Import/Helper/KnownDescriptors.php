<?php

declare(strict_types=1);

namespace App\Application\Import\Helper;

use App\Application\Cookie\Import\CookieData;
use App\Application\CookieProvider\Import\CookieProviderData;

/**
 * @todo: Replace in the future...
 */
final class KnownDescriptors
{
	public const ALL = [
		CookieProviderData::class,
		CookieData::class,
	];

	private function __construct()
	{
	}
}