<?php

declare(strict_types=1);

namespace App\Domain\CookieSuggestion\CommandHandler;

use App\Domain\CookieSuggestion\Command\AddCookieSuggestionOccurrencesCommand;
use App\Domain\CookieSuggestion\CookieSuggestionRepositoryInterface;
use App\Domain\CookieSuggestion\ValueObject\CookieSuggestionId;
use Exception;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class AddCookieSuggestionOccurrencesCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CookieSuggestionRepositoryInterface $cookieSuggestionRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(AddCookieSuggestionOccurrencesCommand $command): void
    {
        if (empty($command->occurrences())) {
            return;
        }

        $cookieSuggestion = $this->cookieSuggestionRepository->get(CookieSuggestionId::fromString($command->cookieSuggestionId()));

        foreach ($command->occurrences() as $occurrence) {
            $cookieSuggestion->addOccurrence($occurrence);
        }

        $this->cookieSuggestionRepository->save($cookieSuggestion);
    }
}
