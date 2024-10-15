<?php

declare(strict_types=1);

namespace App\Web\Ui\Form;

final readonly class RecaptchaResolver
{
    public function __construct(
        private bool $isEnabled,
    ) {}

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
}
