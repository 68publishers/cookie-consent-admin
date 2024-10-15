<?php

declare(strict_types=1);

namespace App\Web\Ui\Form\Validator;

use Contributte\FormMultiplier\Multiplier;
use Nette\Forms\Controls\BaseControl;

final class UniqueMultiplierValuesValidator
{
    public const string Validator = self::class . '::validate';

    private function __construct() {}

    public static function validate(BaseControl $control): bool
    {
        $controlName = $control->getName();
        $multiplier = $control->lookup(Multiplier::class, false);

        if (!$multiplier instanceof Multiplier) {
            return true;
        }

        $currentValue = $control->getValue();

        foreach ($multiplier->getControls() as $multiplierControl) {
            assert($multiplierControl instanceof BaseControl);

            if ($multiplierControl !== $control && $multiplierControl->getName() === $controlName && $multiplierControl->getValue() === $currentValue) {
                return false;
            }
        }

        return true;
    }
}
