<?php

declare(strict_types=1);

namespace App\Application\Localization;

use InvalidArgumentException;
use LogicException;
use SixtyEightPublishers\TranslationBridge\Localization\TranslatorLocalizerInterface;

final class Profiles
{
    private array $profiles;

    private TranslatorLocalizerInterface $translatorLocalizer;

    /**
     * @param Profile[] $profiles
     */
    public function __construct(array $profiles, TranslatorLocalizerInterface $translatorLocalizer)
    {
        $this->profiles = (static fn (Profile ...$profiles): array => $profiles)(...$profiles);
        $this->translatorLocalizer = $translatorLocalizer;
    }

    public function active(): Profile
    {
        $locale = $this->translatorLocalizer->getLocale();

        return $this->has($locale) ? $this->get($locale) : $this->default();
    }

    public function default(): Profile
    {
        if (0 >= count($this->profiles)) {
            throw new LogicException('No profiles registered.');
        }

        return reset($this->profiles);
    }

    public function get(string $code): Profile
    {
        foreach ($this->profiles as $profile) {
            if ($profile->locale() === $code) {
                return $profile;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Profile with code %s is not registered.',
            $code,
        ));
    }

    public function has(string $code): bool
    {
        foreach ($this->profiles as $profile) {
            if ($profile->locale() === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Profile[]
     */
    public function all(): array
    {
        return $this->profiles;
    }
}
