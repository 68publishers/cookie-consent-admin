<?php

declare(strict_types=1);

namespace App\Web\AdminModule\Presenter;

use App\ReadModel\User\UserView;
use Nette\Application\AbortException;
use SixtyEightPublishers\UserBundle\Application\Exception\IdentityException;
use SixtyEightPublishers\UserBundle\Bridge\Nette\Ui\LogoutPresenterTrait;

final class SignOutPresenter extends AdminPresenter
{
    use LogoutPresenterTrait;

    /**
     * @throws IdentityException
     * @throws AbortException
     */
    protected function userLoggedOutHandler(): never
    {
        $userView = $this->getIdentity()->data();
        assert($userView instanceof UserView);

        $this->redirect(':Front:SignIn:', [
            'locale' => $userView->profileLocale->value(),
        ]);
    }
}
