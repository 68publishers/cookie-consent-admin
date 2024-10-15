<?php

declare(strict_types=1);

namespace App\Web\FrontModule\Presenter;

use App\Web\FrontModule\Control\SignIn\Event\AuthenticationFailedEvent;
use App\Web\FrontModule\Control\SignIn\Event\LoggedInEvent;
use App\Web\FrontModule\Control\SignIn\SignInControl;
use App\Web\FrontModule\Control\SignIn\SignInControlFactoryInterface;
use SixtyEightPublishers\FlashMessageBundle\Domain\FlashMessage;
use SixtyEightPublishers\OAuth\OAuthFlowInterface;
use SixtyEightPublishers\OAuth\OAuthFlowProviderInterface;

final class SignInPresenter extends FrontPresenter
{
    /** @persistent */
    public string $backLink = '';

    public function __construct(
        private readonly SignInControlFactoryInterface $signInControlFactory,
        private readonly OAuthFlowProviderInterface $oauthFlowProvider,
    ) {
        parent::__construct();
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $template = $this->getTemplate();
        assert($template instanceof SignInTemplate);

        $template->backLink = !empty($this->backLink) ? $this->backLink : null;
        $template->enabledOauthTypes = array_map(
            static fn (OAuthFlowInterface $flow): string => $flow->getName(),
            array_filter(
                $this->oauthFlowProvider->all(),
                static fn (OAuthFlowInterface $flow): bool => $flow->isEnabled(),
            ),
        );
    }

    protected function createComponentSignIn(): SignInControl
    {
        $control = $this->signInControlFactory->create();

        $control->addEventListener(LoggedInEvent::class, function (): void {
            if (!empty($this->backLink)) {
                $this->restoreRequest($this->backLink);
            }

            $this->redirect(':Admin:Dashboard:');
        });

        $control->addEventListener(AuthenticationFailedEvent::class, function (): void {
            $this->subscribeFlashMessage(FlashMessage::error('user_authentication_failed'));
        });

        return $control;
    }
}
