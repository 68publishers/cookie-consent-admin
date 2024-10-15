<?php

declare(strict_types=1);

namespace App\Web\FrontModule\Control\ForgotPassword;

use App\Web\FrontModule\Control\ForgotPassword\Event\EmailAddressNotFoundEvent;
use App\Web\FrontModule\Control\ForgotPassword\Event\PasswordChangeRequestedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use App\Web\Ui\Form\RecaptchaResolver;
use Nepada\FormRenderer\TemplateRenderer;
use Nette\Application\UI\Form;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ForgotPasswordBundle\Application\Helper\ServerHelpers;
use SixtyEightPublishers\ForgotPasswordBundle\Domain\Command\RequestPasswordChangeCommand;
use SixtyEightPublishers\ForgotPasswordBundle\Domain\Exception\EmailAddressNotFoundException;

final class ForgotPasswordControl extends Control
{
    use FormFactoryOptionsTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly FormFactoryInterface $formFactory,
        private readonly RecaptchaResolver $recaptchaResolver,
    ) {}

    protected function createComponentForm(): Form
    {
        $form = $this->formFactory->create($this->getFormFactoryOptions());
        $renderer = $form->getRenderer();
        assert($renderer instanceof TemplateRenderer);

        $form->setTranslator($this->getPrefixedTranslator());
        $renderer->importTemplate(__DIR__ . '/templates/form.imports.latte');

        $form->addText('email_address')
            ->setRequired('email_address.required')
            ->addRule($form::Email, 'email_address.rule')
            ->setHtmlAttribute('placeholder', 'email_address.field')
            ->setHtmlAttribute('autocomplete', 'username');

        if ($this->recaptchaResolver->isEnabled()) {
            /** @noinspection PhpUndefinedMethodInspection */
            $form->addInvisibleReCaptcha('recaptcha')
                ->setRequired(true)
                ->setMessage('recaptcha.required');
        }

        $form->addSubmit('send', 'send.field');

        $form->onSuccess[] = function (Form $form): void {
            $this->createPasswordRequest($form);
        };

        return $form;
    }

    private function createPasswordRequest(Form $form): void
    {
        $emailAddress = $form->values->email_address;

        try {
            $this->commandBus->dispatch(RequestPasswordChangeCommand::create($emailAddress, ServerHelpers::getIpAddress(), ServerHelpers::getUserAgent()));

            $this->dispatchEvent(new PasswordChangeRequestedEvent($emailAddress));
        } catch (EmailAddressNotFoundException $e) {
            $this->dispatchEvent(new EmailAddressNotFoundEvent($emailAddress));
        }
    }
}
