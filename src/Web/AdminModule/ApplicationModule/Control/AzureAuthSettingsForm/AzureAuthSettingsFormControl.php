<?php

declare(strict_types=1);

namespace App\Web\AdminModule\ApplicationModule\Control\AzureAuthSettingsForm;

use App\Application\GlobalSettings\GlobalSettingsInterface;
use App\Domain\GlobalSettings\Command\PutAzureAuthSettingsCommand;
use App\Web\AdminModule\ApplicationModule\Control\AzureAuthSettingsForm\Event\AzureAuthSettingsUpdatedEvent;
use App\Web\AdminModule\ApplicationModule\Control\AzureAuthSettingsForm\Event\AzureAuthSettingsUpdateFailedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use Throwable;

final class AzureAuthSettingsFormControl extends Control
{
    use FormFactoryOptionsTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CommandBusInterface $commandBus,
        private readonly GlobalSettingsInterface $globalSettings,
    ) {}

    /**
     * @throws InvalidLinkException
     */
    protected function createComponentForm(): Form
    {
        $form = $this->formFactory->create($this->getFormFactoryOptions());
        $translator = $this->getPrefixedTranslator();

        $form->setTranslator($translator);

        $enabledField = $form->addCheckbox('enabled', 'enabled.field');

        $enabledField->addCondition($form::Equal, true)
            ->toggle('#' . $this->getUniqueId() . '-client_id-container')
            ->toggle('#' . $this->getUniqueId() . '-client_secret-container')
            ->toggle('#' . $this->getUniqueId() . '-tenant_id-container')
            ->toggle('#' . $this->getUniqueId() . '-callback_uri-container');

        $form->addText('client_id', 'client_id.field')
            ->setOption('id', $this->getUniqueId() . '-client_id-container')
            ->addConditionOn($enabledField, $form::Equal, true)
                ->setRequired('client_id.required');

        $form->addText('client_secret', 'client_secret.field')
            ->setOption('id', $this->getUniqueId() . '-client_secret-container')
            ->addConditionOn($enabledField, $form::Equal, true)
                ->setRequired('client_secret.required');

        $form->addText('tenant_id', 'tenant_id.field')
            ->setOption('id', $this->getUniqueId() . '-tenant_id-container')
            ->setOption('description', 'tenant_id.description');

        $form->addText('callback_uri', 'callback_uri.field')
            ->setDisabled()
            ->setOmitted()
            ->setValue($this->getPresenter()->link('//:Front:OAuth:authenticate', ['type' => 'azure']))
            ->setOption('id', $this->getUniqueId() . '-callback_uri-container')
            ->setOption('clipboard', 'copy')
            ->setOption('description', 'callback_uri.description');

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('save', 'save.field');

        $defaults = $this->globalSettings->azureAuthSettings();

        $form->setDefaults([
            'enabled' => $defaults->enabled(),
            'client_id' => (string) $defaults->clientId(),
            'client_secret' => (string) $defaults->clientSecret(),
            'tenant_id' => (string) $defaults->tenantId(),
        ]);

        $form->onSuccess[] = function (Form $form): void {
            $this->saveGlobalSettings($form);
        };

        return $form;
    }

    private function saveGlobalSettings(Form $form): void
    {
        $values = $form->getValues();
        $command = PutAzureAuthSettingsCommand::create(
            enabled: $values->enabled,
            clientId: $values->client_id ?: null,
            clientSecret: $values->client_secret ?: null,
            tenantId: $values->tenant_id ?: null,
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            $this->dispatchEvent(new AzureAuthSettingsUpdateFailedEvent($e));

            return;
        }

        $this->dispatchEvent(new AzureAuthSettingsUpdatedEvent());
        $this->redrawControl();
    }
}
