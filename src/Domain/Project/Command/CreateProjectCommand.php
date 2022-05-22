<?php

declare(strict_types=1);

namespace App\Domain\Project\Command;

use SixtyEightPublishers\ArchitectureBundle\Command\AbstractCommand;

final class CreateProjectCommand extends AbstractCommand
{
	/**
	 * @param string      $name
	 * @param string      $code
	 * @param string      $description
	 * @param string      $color
	 * @param bool        $active
	 * @param string|NULL $projectId
	 *
	 * @return static
	 */
	public static function create(string $name, string $code, string $description, string $color, bool $active, ?string $projectId = NULL): self
	{
		return self::fromParameters([
			'name' => $name,
			'code' => $code,
			'description' => $description,
			'color' => $color,
			'active' => $active,
			'project_id' => $projectId,
		]);
	}

	/**
	 * @return string
	 */
	public function name(): string
	{
		return $this->getParam('name');
	}

	/**
	 * @return string
	 */
	public function code(): string
	{
		return $this->getParam('code');
	}

	/**
	 * @return string
	 */
	public function description(): string
	{
		return $this->getParam('description');
	}

	/**
	 * @return string
	 */
	public function color(): string
	{
		return $this->getParam('color');
	}

	/**
	 * @return bool
	 */
	public function active(): bool
	{
		return $this->getParam('active');
	}

	/**
	 * @return string|NULL
	 */
	public function projectId(): ?string
	{
		return $this->getParam('project_id');
	}
}