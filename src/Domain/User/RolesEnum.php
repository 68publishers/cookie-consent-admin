<?php

declare(strict_types=1);

namespace App\Domain\User;

final class RolesEnum
{
	public const ADMIN = 'admin';

	private function __construct()
	{
	}

	/**
	 * @return string[]
	 */
	public static function values(): array
	{
		return [
			self::ADMIN,
		];
	}
}
