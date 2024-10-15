<?php

declare(strict_types=1);

namespace App\Web\AdminModule\CookieModule\Control\CategoryForm;

use App\Application\GlobalSettings\ValidLocalesProvider;
use App\Domain\Category\Command\CreateCategoryCommand;
use App\Domain\Category\Command\UpdateCategoryCommand;
use App\Domain\Category\Exception\CodeUniquenessException;
use App\Domain\Category\ValueObject\CategoryId;
use App\Domain\Category\ValueObject\Code;
use App\Domain\Category\ValueObject\Name;
use App\ReadModel\Category\CategoryView;
use App\Web\AdminModule\CookieModule\Control\CategoryForm\Event\CategoryCreatedEvent;
use App\Web\AdminModule\CookieModule\Control\CategoryForm\Event\CategoryFormProcessingFailedEvent;
use App\Web\AdminModule\CookieModule\Control\CategoryForm\Event\CategoryUpdatedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use Throwable;

final class CategoryFormControl extends Control
{
    use FormFactoryOptionsTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CommandBusInterface $commandBus,
        private readonly ValidLocalesProvider $validLocalesProvider,
        private readonly ?CategoryView $default = null,
    ) {}

    protected function createComponentForm(): Form
    {
        $form = $this->formFactory->create($this->getFormFactoryOptions());
        $translator = $this->getPrefixedTranslator();

        $form->setTranslator($translator);

        $form->addText('code', 'code.field')
            ->setRequired('code.required')
            ->addRule($form::MaxLength, 'code.rule_max_length', Code::MAX_LENGTH);

        $form->addCheckbox('active', 'active.field')
            ->setDefaultValue(true);

        $form->addCheckbox('necessary', 'necessary.field');

        $namesContainer = $form->addContainer('names');

        foreach ($this->validLocalesProvider->getValidLocales() as $locale) {
            $namesContainer->addText($locale->code(), Html::fromText($translator->translate('name.field', ['code' => $locale->code(), 'name' => $locale->name()])))
                ->setRequired('name.required');
        }

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('save', null === $this->default ? 'save.field' : 'update.field');

        if (null !== $this->default) {
            $form->setDefaults([
                'code' => $this->default->code->value(),
                'active' => $this->default->active,
                'necessary' => $this->default->necessary,
                'names' => array_map(static fn (Name $name): string => $name->value(), $this->default->names),
            ]);
        }

        $form->onSuccess[] = function (Form $form): void {
            $this->saveCategory($form);
        };

        return $form;
    }

    private function saveCategory(Form $form): void
    {
        $values = $form->values;

        if (null === $this->default) {
            $categoryId = CategoryId::new();
            $command = CreateCategoryCommand::create(
                $values->code,
                (array) $values->names,
                $values->active,
                $values->necessary,
                $categoryId->toString(),
            );
        } else {
            $categoryId = $this->default->id;
            $command = UpdateCategoryCommand::create($categoryId->toString())
                ->withCode($values->code)
                ->withActive($values->active)
                ->withNecessary($values->necessary)
                ->withNames((array) $values->names);
        }

        try {
            $this->commandBus->dispatch($command);
        } catch (CodeUniquenessException $e) {
            $codeField = $form->getComponent('code');
            assert($codeField instanceof TextInput);

            $codeField->addError('code.error.duplicated_value');

            return;
        } catch (Throwable $e) {
            bdump($e);
            $this->logger->error((string) $e);
            $this->dispatchEvent(new CategoryFormProcessingFailedEvent($e));

            return;
        }

        $this->dispatchEvent(null === $this->default ? new CategoryCreatedEvent($categoryId, $values->code) : new CategoryUpdatedEvent($categoryId, $this->default->code->value(), $values->code));
        $this->redrawControl();
    }
}
