<?php

declare(strict_types=1);

namespace App\Web\AdminModule\CookieModule\Control\ProviderForm;

use App\Application\GlobalSettings\ValidLocalesProvider;
use App\Domain\CookieProvider\Command\CreateCookieProviderCommand;
use App\Domain\CookieProvider\Command\UpdateCookieProviderCommand;
use App\Domain\CookieProvider\Exception\CodeUniquenessException;
use App\Domain\CookieProvider\ValueObject\Code;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use App\Domain\CookieProvider\ValueObject\ProviderType;
use App\Domain\CookieProvider\ValueObject\Purpose;
use App\Domain\Project\Command\AddCookieProvidersToProjectCommand;
use App\Domain\Project\Command\RemoveCookieProvidersFromProjectCommand;
use App\ReadModel\CookieProvider\CookieProviderView;
use App\ReadModel\Project\FindProjectSelectOptionsQuery;
use App\ReadModel\Project\ProjectSelectOptionView;
use App\Web\AdminModule\CookieModule\Control\ProviderForm\Event\ProviderCreatedEvent;
use App\Web\AdminModule\CookieModule\Control\ProviderForm\Event\ProviderFormProcessingFailedEvent;
use App\Web\AdminModule\CookieModule\Control\ProviderForm\Event\ProviderUpdatedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use Throwable;

final class ProviderFormControl extends Control
{
    use FormFactoryOptionsTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly ValidLocalesProvider $validLocalesProvider,
        private readonly ?CookieProviderView $default = null,
    ) {}

    protected function createComponentForm(): Form
    {
        $form = $this->formFactory->create($this->getFormFactoryOptions());
        $translator = $this->getPrefixedTranslator();

        $form->setTranslator($translator);

        $form->addText('code', 'code.field')
            ->setRequired('code.required')
            ->addRule($form::MaxLength, 'code.rule_max_length', Code::MAX_LENGTH);

        $form->addText('name', 'name.field')
            ->setRequired('name.required');

        $form->addRadioList('type', 'type.field')
            ->setItems(ProviderType::values(), false)
            ->setRequired('type.required')
            ->setDefaultValue(ProviderType::THIRD_PARTY);

        $form->addText('link', 'link.field')
            ->addCondition($form::Filled, true)
                ->addRule($form::URL, 'link.rule_url');

        $form->addCheckbox('active', 'active.field')
            ->setDefaultValue(true);

        $form->addMultiSelect('projects', 'projects.field', $this->getProjectOptions())
            ->checkDefaultValue(false)
            ->setTranslator(null)
            ->setOption('tags', true)
            ->setOption('searchbar', true);

        $namesContainer = $form->addContainer('purposes');

        foreach ($this->validLocalesProvider->getValidLocales() as $locale) {
            $namesContainer->addTextArea($locale->code(), Html::fromText($translator->translate('purpose.field', ['code' => $locale->code(), 'name' => $locale->name()])), null, 4);
        }

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('save', null === $this->default ? 'save.field' : 'update.field');

        if (null !== $this->default) {
            $form->setDefaults([
                'code' => $this->default->code->value(),
                'name' => $this->default->name->value(),
                'type' => $this->default->type->value(),
                'link' => $this->default->link->value(),
                'active' => $this->default->active,
                'projects' => $this->getDefaultProjectIds(),
                'purposes' => array_map(static fn (Purpose $purpose): string => $purpose->value(), $this->default->purposes),
            ]);
        }

        $form->onSuccess[] = function (Form $form): void {
            $this->saveProvider($form);
        };

        return $form;
    }

    private function saveProvider(Form $form): void
    {
        $values = $form->values;

        if (null === $this->default) {
            $cookieProviderId = CookieProviderId::new();
            $command = CreateCookieProviderCommand::create(
                $values->code,
                $values->type,
                $values->name,
                $values->link,
                (array) $values->purposes,
                false,
                $values->active,
                $cookieProviderId->toString(),
            );
        } else {
            $cookieProviderId = $this->default->id;
            $command = UpdateCookieProviderCommand::create($cookieProviderId->toString())
                ->withCode($values->code)
                ->withType($values->type)
                ->withName($values->name)
                ->withLink($values->link)
                ->withActive($values->active)
                ->withPurposes((array) $values->purposes);
        }

        try {
            $this->commandBus->dispatch($command);
            $this->saveProjects((array) $values->projects, $cookieProviderId);
        } catch (CodeUniquenessException $e) {
            $codeField = $form->getComponent('code');
            assert($codeField instanceof TextInput);

            $codeField->addError('code.error.duplicated_value');

            return;
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            $this->dispatchEvent(new ProviderFormProcessingFailedEvent($e));

            return;
        }

        $this->dispatchEvent(null === $this->default ? new ProviderCreatedEvent($cookieProviderId, $values->code) : new ProviderUpdatedEvent($cookieProviderId, $this->default->code->value(), $values->code));
        $this->redrawControl();
    }

    /**
     * @param array<string> $projectIds
     */
    private function saveProjects(array $projectIds, CookieProviderId $cookieProviderId): void
    {
        $default = $this->getDefaultProjectIds();

        foreach ($default as $projectId) {
            if (!in_array($projectId, $projectIds, true)) {
                $this->commandBus->dispatch(RemoveCookieProvidersFromProjectCommand::create($projectId, $cookieProviderId->toString()));
            }
        }

        foreach ($projectIds as $projectId) {
            if (!in_array($projectId, $default, true)) {
                $this->commandBus->dispatch(AddCookieProvidersToProjectCommand::create($projectId, $cookieProviderId->toString()));
            }
        }
    }

    private function getProjectOptions(): array
    {
        $options = [];

        /** @var ProjectSelectOptionView $projectSelectOptionView */
        foreach ($this->queryBus->dispatch(FindProjectSelectOptionsQuery::all()) as $projectSelectOptionView) {
            $options += $projectSelectOptionView->toOption();
        }

        return $options;
    }

    private function getDefaultProjectIds(): array
    {
        if (null === $this->default) {
            return [];
        }

        return array_map(
            static fn (ProjectSelectOptionView $view): string => $view->id->toString(),
            $this->queryBus->dispatch(FindProjectSelectOptionsQuery::byCookieProviderId($this->default->id->toString())),
        );
    }
}
