<?php

declare(strict_types=1);

namespace App\Domain\GlobalSettings\ValueObject;

use App\Domain\GlobalSettings\Exception\UnableToCreateEnvironmentSettingsException;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\ComparableValueObjectInterface;

final class EnvironmentSettings implements ComparableValueObjectInterface
{
    public const string DEFAULT_ENVIRONMENT_CODE = 'default';
    private const string DEFAULT_ENVIRONMENT_NAME = 'Default';
    private const string DEFAULT_ENVIRONMENT_COLOR = '#ffffff';

    private function __construct(
        public readonly Environment $defaultEnvironment,
        public readonly Environments $environments,
    ) {}

    public static function createDefault(): self
    {
        return new self(
            defaultEnvironment: new Environment(
                code: EnvironmentCode::fromSafeNative(self::DEFAULT_ENVIRONMENT_CODE),
                name: EnvironmentName::fromSafeNative(self::DEFAULT_ENVIRONMENT_NAME),
                color: Color::fromSafeNative(self::DEFAULT_ENVIRONMENT_COLOR),
            ),
            environments: Environments::empty(),
        );
    }

    public static function fromSafeNative(mixed $native): self
    {
        assert(is_array($native) && isset($native['default_environment']) && isset($native['environments']));

        return new self(
            defaultEnvironment: Environment::fromSafeNative($native['default_environment']),
            environments: Environments::reconstitute($native['environments']),
        );
    }

    /**
     * @throws UnableToCreateEnvironmentSettingsException
     */
    public static function fromNative(mixed $native): self
    {
        if (!is_array($native)) {
            throw UnableToCreateEnvironmentSettingsException::nativeMustBeArray();
        }

        foreach (['default_environment', 'environments'] as $key) {
            if (!isset($native[$key])) {
                throw UnableToCreateEnvironmentSettingsException::missingNativeKey($key);
            }
        }

        if (is_array($native['default_environment'])) {
            $native['default_environment']['code'] = self::DEFAULT_ENVIRONMENT_CODE;
        }

        return new self(
            defaultEnvironment: Environment::fromNative($native['default_environment']),
            environments: Environments::fromNative($native['environments']),
        );
    }

    public function equals(ComparableValueObjectInterface $valueObject): bool
    {
        return $valueObject instanceof self
            && $this->defaultEnvironment->equals($valueObject->defaultEnvironment)
            && $this->environments->equals($valueObject->environments);
    }

    /**
     * @return array{
     *     default_environment: array{
     *         code: string,
     *         name: string,
     *         color: string,
     *     },
     *     environments: array<int, array{
     *         code: string,
     *         name: string,
     *         color: string,
     *     },
     * }
     */
    public function toNative(): array
    {
        return [
            'default_environment' => $this->defaultEnvironment->toNative(),
            'environments' => $this->environments->toArray(),
        ];
    }
}
