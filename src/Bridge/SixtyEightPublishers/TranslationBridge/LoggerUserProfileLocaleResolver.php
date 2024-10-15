<?php

declare(strict_types=1);

namespace App\Bridge\SixtyEightPublishers\TranslationBridge;

use App\ReadModel\User\UserView;
use Nette\Localization\Translator;
use Nette\Security\User as NetteUser;
use SixtyEightPublishers\TranslationBridge\Localization\TranslatorLocaleResolverInterface;
use SixtyEightPublishers\UserBundle\Application\Exception\IdentityException;
use SixtyEightPublishers\UserBundle\Bridge\Nette\Security\Identity;

final readonly class LoggerUserProfileLocaleResolver implements TranslatorLocaleResolverInterface
{
    public function __construct(
        private NetteUser $user,
    ) {}

    /**
     * @throws IdentityException
     */
    public function resolveLocale(Translator $translator): ?string
    {
        if (!$this->user->isLoggedIn()) {
            return null;
        }

        $identity = $this->user->getIdentity();

        if (!$identity instanceof Identity) {
            return null;
        }

        $data = $identity->data();
        assert($data instanceof UserView);

        return $data->profileLocale->value();
    }
}
