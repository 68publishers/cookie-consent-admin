<?php

declare(strict_types=1);

namespace App\Web\Router;

use App\Application\Localization\Profiles;
use App\Web\AdminModule\Presenter\SignOutPresenter;
use Nette\Application\Routers\RouteList;
use Nette\Routing\Route;
use SixtyEightPublishers\UserBundle\Application\Csrf\CsrfTokenFactoryInterface;

final readonly class RouterFactory
{
    public function __construct(
        private Profiles $profiles,
        private CsrfTokenFactoryInterface $csrfTokenFactory,
    ) {}

    public function create(): RouteList
    {
        $router = new RouteList();

        $router->withModule('Front')
            ->addRoute('[<locale [a-z]{2}>/]<presenter>[/<id>]', [
                null => [
                    Route::FilterIn => function (array $params) {
                        if (!in_array($params['presenter'], [
                            'SignIn',
                            'ForgotPassword',
                            'ResetPassword',
                        ], true)) {
                            return null;
                        }

                        if (isset($params['locale']) && !$this->profiles->has($params['locale'])) {
                            $params['locale'] = $this->profiles->default()->locale();
                        }

                        return $params;
                    },
                ],
                'action' => 'default',
                'locale' => $this->profiles->default()->locale(),
            ]);

        $router->addRoute('oauth/<type>/<action>', [
            'module' => 'Front',
            'presenter' => 'OAuth',
        ]);

        $router->addRoute('project/<project>/[<module>/]<presenter>[/<id>]', [
            'module' => 'Admin:Project',
            'action' => 'default',
        ]);

        $router->withModule('Admin')
            ->addRoute('[[<module>/]<presenter>[/<id>]]', [
                'presenter' => 'Dashboard',
                'action' => 'default',
                null => [
                    Route::FilterOut => function (array $parameters) {
                        if ('SignOut' === ($parameters['presenter'] ?? null)) {
                            $parameters['_sec'] = $this->csrfTokenFactory->create(SignOutPresenter::class);
                        }

                        return $parameters;
                    },
                ],
            ]);

        return $router;
    }
}
