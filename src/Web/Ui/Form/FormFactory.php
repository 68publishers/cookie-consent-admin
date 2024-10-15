<?php

declare(strict_types=1);

namespace App\Web\Ui\Form;

use Nepada\FormRenderer\TemplateRendererFactory;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

final readonly class FormFactory implements FormFactoryInterface
{
    public function __construct(
        private TemplateRendererFactory $templateRendererFactory,
    ) {}

    public function create(array $options = []): Form
    {
        $form = new Form();

        if (true === ($options[self::OPTION_AJAX] ?? false)) {
            $form->elementPrototype->class('ajax', true);

            $form->onError[] = static function () use ($form) {
                $parent = $form->getParent();

                if ($parent instanceof Control) {
                    $parent->redrawControl();
                }
            };
        }

        $renderer = $this->templateRendererFactory->create();
        $imports = $options[self::OPTION_IMPORTS] ?? null;
        $templateVariables = $options[self::OPTION_TEMPLATE_VARIABLES] ?? null;

        if (null !== $imports) {
            foreach ((array) $imports as $import) {
                assert(is_string($import));
                $renderer->importTemplate($import);
            }
        }

        if (null !== $templateVariables) {
            $template = $renderer->getTemplate();

            foreach ((array) $templateVariables as $variableName => $variableValue) {
                $template->{$variableName} = $variableValue;
            }
        }

        $form->setRenderer($renderer);

        return $form;
    }
}
