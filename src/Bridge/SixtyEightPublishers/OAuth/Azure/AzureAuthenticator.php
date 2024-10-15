<?php

declare(strict_types=1);

namespace App\Bridge\SixtyEightPublishers\OAuth\Azure;

use App\Application\Localization\ApplicationDateTimeZone;
use App\Application\Localization\Profiles;
use App\Domain\User\Command\StoreExternalAuthenticationCommand;
use App\Domain\User\RolesEnum;
use App\ReadModel\User\UserView;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Nette\Security\IIdentity;
use Psr\Log\LoggerInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\OAuth\Authentication\AuthenticatorInterface;
use SixtyEightPublishers\OAuth\Authorization\AuthorizationResult;
use SixtyEightPublishers\OAuth\Exception\AuthenticationException;
use SixtyEightPublishers\UserBundle\Application\Authentication\Identity;
use SixtyEightPublishers\UserBundle\Application\Authentication\IdentityDecorator;
use SixtyEightPublishers\UserBundle\Application\Exception\IdentityException;
use SixtyEightPublishers\UserBundle\Bridge\Nette\Security\Identity as NetteIdentity;
use SixtyEightPublishers\UserBundle\Domain\Command\CreateUserCommand;
use SixtyEightPublishers\UserBundle\Domain\ValueObject\UserId;
use SixtyEightPublishers\UserBundle\ReadModel\Query\GetUserByEmailAddressQuery;
use TheNetworg\OAuth2\Client\Provider\Azure;
use TheNetworg\OAuth2\Client\Token\AccessToken;
use Throwable;

final readonly class AzureAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private LoggerInterface $logger,
        private Profiles $profiles,
    ) {}

    public function authenticate(string $flowName, AuthorizationResult $authorizationResult): IIdentity
    {
        $userData = $authorizationResult->resourceOwner->toArray();

        $username = $userData['email'] ?? '';
        $firstname = $userData['given_name'] ?? '';
        $surname = $userData['family_name'] ?? '';

        if ('' === $username) {
            try {
                $client = $authorizationResult->client;
                $token = $authorizationResult->accessToken;
                assert($client instanceof Azure && $token instanceof AccessToken);

                $data = $client->get($client->getRootMicrosoftGraphUri($token) . '/v1.0/me', $token);
                $username = $data['mail'] ?? '';
                $firstname = $data['givenName'] ?? $firstname;
                $surname = $data['surname'] ?? $surname;
            } catch (Throwable $e) {
                $this->logger->error(sprintf(
                    'Unable to request profile for user with oid %s via %s.',
                    $authorizationResult->resourceOwner->getId(),
                    $flowName,
                ));

                throw new AuthenticationException($e->getMessage(), 0, $e);
            }
        }

        if ('' === $username) {
            $this->logger->error(sprintf(
                'Unable to login user with oid %s via %s. Missing claim for property "email".',
                $authorizationResult->resourceOwner->getId(),
                $flowName,
            ));

            throw new AuthenticationException('Missing claim for property "email".');
        }

        try {
            $userView = $this->queryBus->dispatch(GetUserByEmailAddressQuery::create(
                emailAddress: $username,
            ));
        } catch (Throwable $e) {
            throw new AuthenticationException($e->getMessage(), 0, $e);
        }

        $roles = [];
        $allRoles = RolesEnum::values();

        foreach ((array) ($userData['roles'] ?? []) as $role) {
            if (in_array($role, $allRoles, true)) {
                $roles[] = $role;
            }
        }

        if (0 >= count($roles)) {
            $roles = [
                RolesEnum::MANAGER,
            ];
        }

        if (!$userView instanceof UserView) {
            $userId = $this->createUser(
                resourceOwner: $authorizationResult->resourceOwner,
                flowName: $flowName,
                username: $username,
                roles: $roles,
                firstname: (string) $firstname,
                surname: (string) $surname,
            );
        } else {
            $userId = $userView->id->toString();
        }

        $this->storeExternalAuth(
            authorizationResult: $authorizationResult,
            flowName: $flowName,
            userId: $userId,
            roles: $roles,
        );

        $identity = IdentityDecorator::newInstance()->wakeupIdentity(
            identity: Identity::createSleeping(UserId::fromString($userId)),
            queryBus: $this->queryBus,
        );

        $identity = NetteIdentity::of($identity);

        try {
            $identity->data();
        } catch (IdentityException $e) {
            throw new AuthenticationException($e->getMessage(), 0, $e);
        }

        return $identity;
    }

    /**
     * @param array<int, string> $roles
     */
    private function createUser(ResourceOwnerInterface $resourceOwner, string $flowName, string $username, array $roles, string $firstname, string $surname): string
    {
        $userId = UserId::new()->toString();

        try {
            $command = CreateUserCommand::create(
                username: $username,
                password: null,
                emailAddress: $username,
                firstname: $firstname,
                surname: $surname,
                roles: $roles,
                userId: $userId,
            );

            $command = $command
                ->withParam('profile', $this->profiles->active()->locale())
                ->withParam('timezone', ApplicationDateTimeZone::get()->getName());

            $this->commandBus->dispatch($command);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Unable to create the user with oid %s via %s. %s',
                $resourceOwner->getId(),
                $flowName,
                $e->getMessage(),
            ), [
                'exception' => $e,
            ]);

            throw new AuthenticationException($e->getMessage(), 0, $e);
        }

        return $userId;
    }

    private function storeExternalAuth(AuthorizationResult $authorizationResult, string $flowName, string $userId, array $roles): void
    {
        try {
            $this->commandBus->dispatch(StoreExternalAuthenticationCommand::create(
                userId: $userId,
                providerCode: $flowName,
                resourceOwnerId: (string) $authorizationResult->resourceOwner->getId(),
                token: $authorizationResult->accessToken->getToken(),
                refreshToken: (string) $authorizationResult->accessToken->getRefreshToken(),
                roles: $roles,
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Unable to update the user with oid %s via %s. %s',
                $authorizationResult->resourceOwner->getId(),
                $flowName,
                $e->getMessage(),
            ), [
                'exception' => $e,
            ]);

            throw new AuthenticationException($e->getMessage(), 0, $e);
        }
    }
}
