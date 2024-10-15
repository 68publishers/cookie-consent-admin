<?php

declare(strict_types=1);

namespace App\Web\Ui\DataGrid\Translator;

use Nette\Localization\Translator;

final readonly class TranslatorProxy implements Translator
{
    public function __construct(
        private Translator $translator,
    ) {}

    public function translate($message, ...$parameters): string
    {
        if (is_string($message) && str_starts_with($message, 'ublaboo_datagrid')) {
            $message = '//' . $message;
        }

        return $this->translator->translate($message, ...$parameters);
    }
}
