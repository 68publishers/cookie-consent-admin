<?php

declare(strict_types=1);

namespace App\Subscribers\PasswordRequest;

use App\Application\Mail\Address;
use App\Application\Mail\Command\SendMailCommand;
use App\Application\Mail\Message;
use App\ReadModel\User\UserView;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Event\EventHandlerInterface;
use SixtyEightPublishers\ForgotPasswordBundle\Domain\Event\PasswordChangeCompleted;
use SixtyEightPublishers\UserBundle\ReadModel\Query\GetUserByEmailAddressQuery;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class SendPasswordChangedMail implements EventHandlerInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(PasswordChangeCompleted $event): void
    {
        $userView = $this->queryBus->dispatch(GetUserByEmailAddressQuery::create($event->emailAddress()->value()));
        $locale = $userView instanceof UserView ? $userView->profileLocale->value() : null;

        $message = Message::create('default:password_has_been_reset', $locale)
            ->withTo(Address::create($event->emailAddress()->value()))
            ->withArguments([
                'emailAddress' => $event->emailAddress()->value(),
            ]);

        $this->commandBus->dispatch(SendMailCommand::create($message), [
            new DispatchAfterCurrentBusStamp(),
        ]);
    }
}
