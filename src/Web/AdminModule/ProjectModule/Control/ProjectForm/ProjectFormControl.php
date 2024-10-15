<?php

declare(strict_types=1);

namespace App\Web\AdminModule\ProjectModule\Control\ProjectForm;

use App\Application\GlobalSettings\GlobalSettingsInterface;
use App\Domain\GlobalSettings\ValueObject\Environment;
use App\Domain\Project\Command\CreateProjectCommand;
use App\Domain\Project\Command\UpdateProjectCommand;
use App\Domain\Project\Exception\CodeUniquenessException;
use App\Domain\Project\ValueObject\Code;
use App\Domain\Project\ValueObject\ProjectId;
use App\ReadModel\Project\ProjectView;
use App\Web\AdminModule\ProjectModule\Control\ProjectForm\Event\ProjectCreatedEvent;
use App\Web\AdminModule\ProjectModule\Control\ProjectForm\Event\ProjectFormProcessingFailedEvent;
use App\Web\AdminModule\ProjectModule\Control\ProjectForm\Event\ProjectUpdatedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use NasExt\Forms\Controls\DependentSelectBox;
use NasExt\Forms\DependentData;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Controls\TextInput;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use Throwable;

final class ProjectFormControl extends Control
{
    use FormFactoryOptionsTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CommandBusInterface $commandBus,
        private readonly GlobalSettingsInterface $globalSettings,
        private readonly ?ProjectView $default = null,
    ) {}

    protected function createComponentForm(): Form
    {
        $globalLocales = [];
        $environments = [];

        foreach ($this->globalSettings->locales() as $locale) {
            $globalLocales[$locale->code()] = sprintf('%s - %s', $locale->name(), $locale->code());
        }

        foreach ($this->globalSettings->environmentSettings()->environments->all() as $environment) {
            assert($environment instanceof Environment);
            $environments[$environment->code->value()] = $environment->name->value();
        }

        $form = $this->formFactory->create(array_merge(
            $this->getFormFactoryOptions(),
            [
                FormFactoryInterface::OPTION_IMPORTS => __DIR__ . '/templates/form.imports.latte',
                FormFactoryInterface::OPTION_TEMPLATE_VARIABLES => [
                    'environmentSettings' => $this->globalSettings->environmentSettings(),
                ],
            ],
        ));
        $translator = $this->getPrefixedTranslator();

        $form->setTranslator($translator);

        $form->addText('name', 'name.field')
            ->setRequired('name.required');

        $form->addText('code', 'code.field')
            ->setRequired('code.required')
            ->addRule($form::Pattern, 'code.rule_pattern', '[a-z0-9_\-\.]+')
            ->addRule($form::MaxLength, 'code.rule_max_length', Code::MAX_LENGTH)
            ->setOption('description', 'code.description');

        $form->addText('domain', 'domain.field')
            ->setRequired('domain.field')
            ->setOption('description', 'domain.description');

        $form->addText('color', 'color.field')
            ->setDefaultValue('#ffffff')
            ->setRequired('color.required')
            ->addRule($form::Pattern, 'color.rule_pattern', '#([a-fA-F0-9]{3}){1,2}\b')
            ->setOption('type', 'color-picker');

        $form->addCheckbox('active', 'active.field')
            ->setDefaultValue(true);

        $form->addMultiSelect('locales', 'locales.field', $globalLocales)
            ->checkDefaultValue(false)
            ->setTranslator(null)
            ->setOption('tags', true)
            ->setRequired('locales.required');

        $form->addComponent(
            (new DependentSelectBox('default_locale.field', [$form->getComponent('locales')]))
                ->setDependentCallback(function ($values) use ($globalLocales) {
                    $locales = $values['locales'];

                    if (empty($locales)) {
                        return new DependentData([]);
                    }

                    $defaultValue = $this->default?->locales->defaultLocale()->value();
                    $defaultValue = in_array($defaultValue, $locales, true) ? $defaultValue : null;

                    if (null === $defaultValue && 0 < count($locales)) {
                        $defaultValue = reset($locales);
                    }

                    return new DependentData(
                        array_filter($globalLocales, static fn (string $loc): bool => in_array($loc, $locales, true), ARRAY_FILTER_USE_KEY),
                        $defaultValue,
                    );
                })
                ->setPrompt('-------')
                ->checkDefaultValue(false)
                ->setTranslator(null)
                ->setRequired('default_locale.required'),
            'default_locale',
        );

        $form->addCheckboxList('environments', 'environments.field')
            ->checkDefaultValue(false)
            ->setItems(['' => $this->globalSettings->environmentSettings()->defaultEnvironment->name->value()] + $environments)
            ->setTranslator(null)
            ->setDisabled([''])
            ->setDefaultValue(['']);

        $form->addTextArea('description', 'description.field', null, 4);

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('save', null === $this->default ? 'save.field' : 'update.field');

        if (null !== $this->default) {
            $form->setDefaults([
                'name' => $this->default->name->value(),
                'code' => $this->default->code->value(),
                'domain' => $this->default->domain->value(),
                'color' => $this->default->color->value(),
                'active' => $this->default->active,
                'locales' => $this->default->locales->locales()->toArray(),
                'default_locale' => $this->default->locales->defaultLocale()->value(),
                'description' => $this->default->description->value(),
                'environments' => array_merge(
                    [''],
                    $this->default->environments->toArray(),
                ),
            ]);
        }

        $form->onSuccess[] = function (Form $form): void {
            $this->saveProject($form);
        };

        return $form;
    }

    private function saveProject(Form $form): void
    {
        $values = $form->values;

        if (null === $this->default) {
            $projectId = ProjectId::new();
            $command = CreateProjectCommand::create(
                name: $values->name,
                code: $values->code,
                domain: $values->domain,
                description: $values->description,
                color: $values->color,
                active: $values->active,
                locales: $values->locales,
                defaultLocale: $values->default_locale,
                environments: $values->environments,
                projectId: $projectId->toString(),
            );
        } else {
            $projectId = $this->default->id;
            $command = UpdateProjectCommand::create($projectId->toString())
                ->withName($values->name)
                ->withCode($values->code)
                ->withDomain($values->domain)
                ->withDescription($values->description)
                ->withColor($values->color)
                ->withActive($values->active)
                ->withLocales($values->locales, $values->default_locale)
                ->withEnvironments($values->environments);
        }

        try {
            $this->commandBus->dispatch($command);
        } catch (CodeUniquenessException $e) {
            $codeField = $form->getComponent('code');
            assert($codeField instanceof TextInput);

            $codeField->addError('code.error.duplicated_value');

            return;
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            $this->dispatchEvent(new ProjectFormProcessingFailedEvent($e));

            return;
        } finally {
            $environments = $form->getComponent('environments');
            assert($environments instanceof CheckboxList);

            # Set default environment back because the option is disabled, and we lost the state when form submitted.
            $environments->setValue(array_merge(
                [''],
                $environments->getValue(),
            ));
        }

        $this->dispatchEvent(null === $this->default ? new ProjectCreatedEvent($projectId, $values->code) : new ProjectUpdatedEvent($projectId, $this->default->code->value(), $values->code));
        $this->redrawControl();
    }
}
