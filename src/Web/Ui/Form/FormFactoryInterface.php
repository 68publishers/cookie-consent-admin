<?php

declare(strict_types=1);

namespace App\Web\Ui\Form;

use Nette\Application\UI\Form;

interface FormFactoryInterface
{
    public const string OPTION_AJAX = 'ajax';
    public const string OPTION_IMPORTS = 'imports';
    public const string OPTION_TEMPLATE_VARIABLES = 'template_variables';

    public function create(array $options = []): Form;
}
