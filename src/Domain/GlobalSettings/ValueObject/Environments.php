<?php

declare(strict_types=1);

namespace App\Domain\GlobalSettings\ValueObject;

use App\Domain\GlobalSettings\Exception\UnableToCreateEnvironmentsException;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractValueObjectSet;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\ComparableValueObjectInterface;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\ValueObjectSetInterface;

final class Environments extends AbstractValueObjectSet
{
    public const string ITEM_CLASSNAME = Environment::class;

    /**
     * @throws UnableToCreateEnvironmentsException
     */
    public static function fromNative(mixed $native): self
    {
        if (!is_array($native)) {
            throw UnableToCreateEnvironmentsException::nativeMustBeArray();
        }

        $environments = array_map(
            static function (mixed $nativeEnvironment): Environment {
                $environment = Environment::fromNative($nativeEnvironment);

                if (EnvironmentSettings::DEFAULT_ENVIRONMENT_CODE === $environment->code->value()) {
                    throw UnableToCreateEnvironmentsException::nativeMustBeArray();
                }

                return $environment;
            },
            $native,
        );

        return self::fromItems($environments);
    }

    public function getByCode(string $code): ?Environment
    {
        foreach ($this->items as $item) {
            assert($item instanceof Environment);

            if ($item->code->value() === $code) {
                return $item;
            }
        }

        return null;
    }

    public function with(ComparableValueObjectInterface $item): ValueObjectSetInterface
    {
        assert($item instanceof Environment);

        if (EnvironmentSettings::DEFAULT_ENVIRONMENT_CODE === $item->code->value()) {
            throw UnableToCreateEnvironmentsException::nativeMustBeArray();
        }

        return parent::with($item);
    }

    /**
     * @param array $value
     */
    protected static function reconstituteItem($value): Environment
    {
        return Environment::fromSafeNative($value);
    }

    /**
     * @param Environment $item
     *
     * @return array{
     *     code: string,
     *     name: string,
     *     color: string,
     * }
     */
    protected static function exportItem($item): array
    {
        assert($item instanceof Environment);

        return $item->toNative();
    }
}
