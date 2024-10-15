<?php

declare(strict_types=1);

namespace App\Web\AdminModule\ProjectModule\Control\ProviderForm;

use App\Application\GlobalSettings\ValidLocalesProvider;
use App\Domain\CookieProvider\Command\UpdateCookieProviderCommand;
use App\Domain\CookieProvider\Exception\CodeUniquenessException;
use App\Domain\CookieProvider\ValueObject\Code;
use App\Domain\CookieProvider\ValueObject\Purpose;
use App\ReadModel\CookieProvider\CookieProviderView;
use App\ReadModel\CookieProvider\GetCookieProviderByIdQuery;
use App\ReadModel\Project\ProjectView;
use App\Web\AdminModule\ProjectModule\Control\ProviderForm\Event\ProviderFormProcessingFailedEvent;
use App\Web\AdminModule\ProjectModule\Control\ProviderForm\Event\ProviderUpdatedEvent;
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

    private ?CookieProviderView $default = null;

    public function __construct(
        private readonly ProjectView $projectView,
        private readonly FormFactoryInterface $formFactory,
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly ValidLocalesProvider $validLocalesProvider,
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

        $form->addText('link', 'link.field')
            ->addCondition($form::Filled, true)
                ->addRule($form::URL, 'link.rule_url');

        $namesContainer = $form->addContainer('purposes');

        foreach ($this->validLocalesProvider->getValidLocales($this->projectView->locales) as $locale) {
            $namesContainer->addTextArea($locale->code(), Html::fromText($translator->translate('purpose.field', ['code' => $locale->code(), 'name' => $locale->name()])), null, 4);
        }

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('save', 'update.field');

        $default = $this->getDefault();

        if (null !== $default) {
            $form->setDefaults([
                'code' => $default->code->value(),
                'name' => $default->name->value(),
                'link' => $default->link->value(),
                'purposes' => array_map(static fn (Purpose $purpose): string => $purpose->value(), $default->purposes),
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
        $cookieProviderId = $this->projectView->cookieProviderId;
        $default = $this->getDefault();

        $command = UpdateCookieProviderCommand::create($cookieProviderId->toString())
            ->withCode($values->code)
            ->withName($values->name)
            ->withLink($values->link)
            ->withPurposes((array) $values->purposes);

        try {
            $this->commandBus->dispatch($command);
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

        $this->dispatchEvent(new ProviderUpdatedEvent($cookieProviderId, null !== $default ? $default->code->value() : '', $values->code));
        $this->redrawControl();
    }

    private function getDefault(): ?CookieProviderView
    {
        return $this->default ?? $this->default = $this->queryBus->dispatch(GetCookieProviderByIdQuery::create($this->projectView->cookieProviderId->toString()));
    }
}
