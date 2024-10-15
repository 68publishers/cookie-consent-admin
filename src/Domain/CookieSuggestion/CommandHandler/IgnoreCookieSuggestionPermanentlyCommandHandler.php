<?php

declare(strict_types=1);

namespace App\Domain\CookieSuggestion\CommandHandler;

use App\Domain\CookieSuggestion\Command\IgnoreCookieSuggestionPermanentlyCommand;
use App\Domain\CookieSuggestion\CookieSuggestionRepositoryInterface;
use App\Domain\CookieSuggestion\ValueObject\CookieSuggestionId;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class IgnoreCookieSuggestionPermanentlyCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CookieSuggestionRepositoryInterface $cookieSuggestionRepository,
    ) {}

    public function __invoke(IgnoreCookieSuggestionPermanentlyCommand $command): void
    {
        $cookieSuggestion = $this->cookieSuggestionRepository->get(CookieSuggestionId::fromString($command->cookieSuggestionId()));

        $cookieSuggestion->ignorePermanently();

        $this->cookieSuggestionRepository->save($cookieSuggestion);
    }
}
