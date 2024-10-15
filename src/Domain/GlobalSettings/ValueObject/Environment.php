<?php

declare(strict_types=1);

namespace App\Domain\GlobalSettings\ValueObject;

use App\Domain\GlobalSettings\Exception\UnableToCreateEnvironmentException;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\ComparableValueObjectInterface;

final readonly class Environment implements ComparableValueObjectInterface
{
    public function __construct(
        public EnvironmentCode $code,
        public EnvironmentName $name,
        public Color $color,
    ) {}

    public static function fromSafeNative(mixed $native): self
    {
        assert(is_array($native) && isset($native['code']) && isset($native['name']) && isset($native['color']));

        return new self(
            code: EnvironmentCode::fromSafeNative($native['code']),
            name: EnvironmentName::fromSafeNative($native['name']),
            color: Color::fromValue($native['color']),
        );
    }

    /**
     * @throws UnableToCreateEnvironmentException
     */
    public static function fromNative(mixed $native): self
    {
        if (!is_array($native)) {
            throw UnableToCreateEnvironmentException::nativeMustBeArray();
        }

        foreach (['code', 'name', 'color'] as $key) {
            if (!isset($native[$key])) {
                throw UnableToCreateEnvironmentException::missingNativeKey($key);
            }
        }

        return new self(
            code: EnvironmentCode::fromNative($native['code']),
            name: EnvironmentName::fromNative($native['name']),
            color: Color::fromNative($native['color']),
        );
    }

    public function equals(ComparableValueObjectInterface $valueObject): bool
    {
        return $valueObject instanceof self
            && $valueObject->code === $this->code
            && $valueObject->name === $this->name
            && $valueObject->color->equals($this->color);
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     color: string,
     * }
     */
    public function toNative(): array
    {
        return [
            'code' => $this->code->value(),
            'name' => $this->name->value(),
            'color' => $this->color->value(),
        ];
    }
}
