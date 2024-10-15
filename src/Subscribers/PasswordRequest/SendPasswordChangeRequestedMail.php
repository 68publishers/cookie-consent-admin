<?php

declare(strict_types=1);

namespace App\Subscribers\PasswordRequest;

use App\Application\Mail\Address;
use App\Application\Mail\Command\SendMailCommand;
use App\Application\Mail\Message;
use App\ReadModel\User\UserView;
use DateTimeZone;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Event\EventHandlerInterface;
use SixtyEightPublishers\ForgotPasswordBundle\Domain\Event\PasswordChangeRequested;
use SixtyEightPublishers\UserBundle\ReadModel\Query\GetUserByEmailAddressQuery;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class SendPasswordChangeRequestedMail implements EventHandlerInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private LinkGenerator $linkGenerator,
    ) {}

    /**
     * @throws InvalidLinkException
     */
    public function __invoke(PasswordChangeRequested $event): void
    {
        $userView = $this->queryBus->dispatch(GetUserByEmailAddressQuery::create($event->emailAddress()->value()));
        $locale = $userView instanceof UserView ? $userView->profileLocale->value() : null;
        $timezone = $userView instanceof UserView ? $userView->timezone : new DateTimeZone('UTC');

        $message = Message::create('default:password_change_requested', $locale)
            ->withTo(Address::create($event->emailAddress()->value()))
            ->withArguments([
                'emailAddress' => $event->emailAddress()->value(),
                'passwordRequestId' => $event->passwordRequestId()->toString(),
                'expireAt' => $event->expiredAt()->setTimezone($timezone)->format('j.n.Y H:i'),
                'resetLink' => $this->linkGenerator->link('Front:ResetPassword:', [
                    'id' => $event->passwordRequestId()->toString(),
                    'locale' => $locale,
                ]),
            ]);

        $this->commandBus->dispatch(SendMailCommand::create($message), [
            new DispatchAfterCurrentBusStamp(),
        ]);
    }
}
