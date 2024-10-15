<?php

declare(strict_types=1);

namespace App\Web\AdminModule\CookieModule\Control\CookieForm;

use App\Application\GlobalSettings\EnabledEnvironmentsResolver;
use App\Application\GlobalSettings\GlobalSettingsInterface;
use App\Application\GlobalSettings\ValidLocalesProvider;
use App\Domain\Cookie\Command\CreateCookieCommand;
use App\Domain\Cookie\Command\UpdateCookieCommand;
use App\Domain\Cookie\Exception\NameUniquenessException;
use App\Domain\Cookie\ValueObject\CookieId;
use App\Domain\Cookie\ValueObject\Environment as CookieEnvironment;
use App\Domain\Cookie\ValueObject\ProcessingTime;
use App\Domain\Cookie\ValueObject\Purpose;
use App\Domain\CookieProvider\ValueObject\CookieProviderId;
use App\Domain\GlobalSettings\ValueObject\Environment;
use App\ReadModel\Category\AllCategoriesQuery;
use App\ReadModel\Category\CategoryView;
use App\ReadModel\Cookie\CookieView;
use App\ReadModel\CookieProvider\CookieProviderSelectOptionView;
use App\ReadModel\CookieProvider\FindCookieProviderSelectOptionsQuery;
use App\Web\AdminModule\CookieModule\Control\CookieForm\Event\CookieCreatedEvent;
use App\Web\AdminModule\CookieModule\Control\CookieForm\Event\CookieFormProcessingFailedEvent;
use App\Web\AdminModule\CookieModule\Control\CookieForm\Event\CookieUpdatedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use Closure;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use Throwable;

final class CookieFormControl extends Control
{
    use FormFactoryOptionsTrait;

    private ?CookieProviderId $cookieProviderId = null;

    private ?array $providerOptions = null;

    private ?Closure $formProcessor = null;

    private ?array $overwrittenDefaults = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly ValidLocalesProvider $validLocalesProvider,
        private readonly GlobalSettingsInterface $globalSettings,
        private readonly ?CookieView $default = null,
    ) {}

    public function setCookieProviderId(CookieProviderId $cookieProviderId): self
    {
        $this->cookieProviderId = $cookieProviderId;

        return $this;
    }

    public function setFormProcessor(Closure $formProcessor): void
    {
        $this->formProcessor = $formProcessor;
    }

    public function setOverwrittenDefaults(array $overwrittenDefaults): void
    {
        $this->overwrittenDefaults = $overwrittenDefaults;
    }

    /**
     * @param array<string, CookieProviderSelectOptionView> $providerOptions
     */
    public function setCookieProviderOptions(array $providerOptions): void
    {
        $this->providerOptions = $providerOptions;
    }

    protected function createComponentForm(): Form
    {
        $translator = $this->getPrefixedTranslator();
        $providers = $this->getCookieProviders();
        $environments = EnabledEnvironmentsResolver::resolveAllEnvironments($this->globalSettings->environmentSettings());

        $form = $this->formFactory->create(array_merge(
            $this->getFormFactoryOptions(),
            [
                FormFactoryInterface::OPTION_IMPORTS => __DIR__ . '/templates/form.imports.latte',
                FormFactoryInterface::OPTION_TEMPLATE_VARIABLES => [
                    'providers' => $providers,
                    'environments' => $environments,
                ],
            ],
        ));

        $form->setTranslator($translator);

        $form->addText('name', 'name.field')
            ->setRequired('name.required');

        $form->addText('domain', 'domain.field')
            ->setOption('description', 'domain.description');

        $providerField = $form->addSelect('provider', 'provider.field', array_map(static fn (CookieProviderSelectOptionView $view): string => $view->name->value(), $providers))
            ->setPrompt('-------')
            ->setRequired('provider.required')
            ->setTranslator(null)
            ->checkDefaultValue(false)
            ->setOption('searchbar', true);

        $form->addSelect('category', 'category.field', $this->getCategories())
            ->setPrompt('-------')
            ->setRequired('category.required')
            ->setTranslator(null)
            ->checkDefaultValue(false);

        $form->addCheckbox('all_environments', 'all_environments.field')
            ->setDefaultValue(true)
            ->addCondition(Form::Equal, false)
            ->toggle('#' . $this->getUniqueId() . '-environments-container');

        $form->addCheckboxList('environments', 'environments.field')
            ->checkDefaultValue(false)
            ->setItems(array_map(
                static fn (Environment $environment): string => $environment->name->value(),
                $environments,
            ))
            ->setTranslator(null)
            ->setOption('id', $this->getUniqueId() . '-environments-container');

        $form->addRadioList('processing_time', 'processing_time.field')
            ->setItems([ProcessingTime::PERSISTENT, ProcessingTime::SESSION, 'expiration'], false)
            ->setRequired('processing_time.required')
            ->setDefaultValue(ProcessingTime::PERSISTENT)
            ->addCondition($form::Equal, 'expiration')
                ->toggle('#' . $this->getUniqueId() . '-processing_time_mask');

        $form->addText('processing_time_mask', 'processing_time_mask.field')
            ->setOption('id', $this->getUniqueId() . '-processing_time_mask')
            ->setOption('description', 'processing_time_mask.description')
            ->addConditionOn($form->getComponent('processing_time'), $form::Equal, 'expiration')
                ->setRequired('processing_time_mask.required')
                ->addRule($form::Pattern, 'processing_time_mask.rule_pattern', '(?:(?<years>\d+)y\s*)?(?:(?<months>\d+)m\s*)?(?:(?<days>\d+)d\s*)?(?:(?<hours>\d+)h\s*)?(?:(?<minutes>\d+)i\s*)?(?:(?<seconds>\d+)s\s*)?');

        $form->addCheckbox('active', 'active.field')
            ->setDefaultValue(true);

        $purposesContainer = $form->addContainer('purposes');

        foreach ($this->validLocalesProvider->getValidLocales() as $locale) {
            $purposesContainer->addTextArea($locale->code(), Html::fromText($translator->translate('purpose.field', ['code' => $locale->code(), 'name' => $locale->name()])), null, 4);
        }

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('save', null === $this->default ? 'save.field' : 'update.field');

        if (null !== $this->default) {
            $isExpiration = !in_array($this->default->processingTime->value(), [ProcessingTime::PERSISTENT, ProcessingTime::SESSION], true);

            $form->setDefaults([
                'name' => $this->default->name->value(),
                'domain' => $this->default->domain->value(),
                'provider' => $providerDefaultValue = $this->default->cookieProviderId->toString(),
                'category' => $this->default->categoryId->toString(),
                'all_environments' => $this->default->allEnvironments,
                'environments' => array_map(
                    static fn (CookieEnvironment $environment): string => $environment->value(),
                    $this->default->environments->all(),
                ),
                'processing_time' => !$isExpiration ? $this->default->processingTime->value() : 'expiration',
                'processing_time_mask' => $isExpiration ? $this->default->processingTime->value() : '',
                'active' => $this->default->active,
                'purposes' => array_map(static fn (Purpose $purpose): string => $purpose->value(), $this->default->purposes),
            ]);
        } elseif (null !== $this->cookieProviderId) {
            $form->setDefaults([
                'provider' => $providerDefaultValue = $this->cookieProviderId->toString(),
            ]);
        }

        if (null !== $this->overwrittenDefaults) {
            $form->setDefaults($this->overwrittenDefaults);
        }

        if (isset($providerDefaultValue) && array_key_exists($providerDefaultValue, $providers)) {
            $providerField->setDisabled()
                ->setOmitted(false)
                ->setDefaultValue($providerDefaultValue);
        }

        $form->onSuccess[] = function (Form $form): void {
            $this->saveCookie($form);
        };

        return $form;
    }

    private function saveCookie(Form $form): void
    {
        if (null !== $this->formProcessor) {
            ($this->formProcessor)($form, $this);

            return;
        }

        $values = $form->values;
        $environments = $values->all_environments ? true : $values->environments;

        if (null === $this->default) {
            $cookieId = CookieId::new();
            $command = CreateCookieCommand::create(
                categoryId: $values->category,
                cookieProviderId: $values->provider,
                name: $values->name,
                domain: $values->domain,
                processingTime: 'expiration' === $values->processing_time ? $values->processing_time_mask : $values->processing_time,
                active: $values->active,
                purposes: (array) $values->purposes,
                environments: $environments,
                cookieId: $cookieId->toString(),
            );
        } else {
            $cookieId = $this->default->id;
            $command = UpdateCookieCommand::create($cookieId->toString())
                ->withCategoryId($values->category)
                ->withName($values->name)
                ->withDomain($values->domain)
                ->withProcessingTime('expiration' === $values->processing_time ? $values->processing_time_mask : $values->processing_time)
                ->withActive($values->active)
                ->withPurposes((array) $values->purposes)
                ->withEnvironments($environments);
        }

        try {
            $this->commandBus->dispatch($command);
        } catch (NameUniquenessException $e) {
            $nameField = $form->getComponent('name');
            assert($nameField instanceof TextInput);

            $nameField->addError('name.error.duplicated_value');

            return;
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            $this->dispatchEvent(new CookieFormProcessingFailedEvent($e));

            return;
        }

        $this->dispatchEvent(null === $this->default ? new CookieCreatedEvent($cookieId, $values->name) : new CookieUpdatedEvent($cookieId, $this->default->name->value(), $values->name));
        $this->redrawControl();
    }

    private function getCategories(): array
    {
        $categories = [];
        $locale = $this->validLocalesProvider->getValidDefaultLocale();

        foreach ($this->queryBus->dispatch(AllCategoriesQuery::create()) as $categoryView) {
            assert($categoryView instanceof CategoryView);

            $categories[$categoryView->id->toString()] = null !== $locale && isset($categoryView->names[$locale->code()]) ? $categoryView->names[$locale->code()]->value() : $categoryView->code->value();
        }

        return $categories;
    }

    /**
     * @return array<string, CookieProviderSelectOptionView>
     */
    private function getCookieProviders(): array
    {
        if (null !== $this->providerOptions) {
            return $this->providerOptions;
        }

        $options = [];
        $query = FindCookieProviderSelectOptionsQuery::all()
            ->withPrivate(true);

        foreach ($this->queryBus->dispatch($query) as $cookieProviderSelectOptionView) {
            assert($cookieProviderSelectOptionView instanceof CookieProviderSelectOptionView);

            $options[$cookieProviderSelectOptionView->id->toString()] = $cookieProviderSelectOptionView;
        }

        return $this->providerOptions = $options;
    }
}
