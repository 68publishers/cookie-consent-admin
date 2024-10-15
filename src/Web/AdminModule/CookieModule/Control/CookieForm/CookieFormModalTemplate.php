<?php

declare(strict_types=1);

namespace App\Web\AdminModule\CookieModule\Control\CookieForm;

use App\ReadModel\Cookie\CookieView;
use App\Web\Ui\Modal\AbstractModalTemplate;

final class CookieFormModalTemplate extends AbstractModalTemplate
{
    public ?CookieView $default = null;
}
