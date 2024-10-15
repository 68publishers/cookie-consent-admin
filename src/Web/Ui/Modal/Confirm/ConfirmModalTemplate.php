<?php

declare(strict_types=1);

namespace App\Web\Ui\Modal\Confirm;

use App\Web\Ui\Modal\AbstractModalTemplate;
use Nette\Utils\Html;

final class ConfirmModalTemplate extends AbstractModalTemplate
{
    public Html $title;

    public Html $question;

    public array $args = [];

    public ?string $yesButtonText = null;

    public ?string $noButtonText = null;
}
