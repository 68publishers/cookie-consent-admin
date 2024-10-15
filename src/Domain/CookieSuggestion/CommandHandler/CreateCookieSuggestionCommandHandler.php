<?php

declare(strict_types=1);

namespace App\Domain\CookieSuggestion\CommandHandler;

use App\Domain\CookieSuggestion\CheckSuggestionNameAndDomainUniquenessInterface;
use App\Domain\CookieSuggestion\Command\CreateCookieSuggestionCommand;
use App\Domain\CookieSuggestion\CookieSuggestion;
use App\Domain\CookieSuggestion\CookieSuggestionRepositoryInterface;
use Exception;
use SixtyEightPublishers\ArchitectureBundle\Command\CommandHandlerInterface;

final readonly class CreateCookieSuggestionCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CookieSuggestionRepositoryInterface $cookieSuggestionRepository,
        private CheckSuggestionNameAndDomainUniquenessInterface $checkSuggestionNameAndDomainUniqueness,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(CreateCookieSuggestionCommand $command): void
    {
        $cookieSuggestion = CookieSuggestion::create($command, $this->checkSuggestionNameAndDomainUniqueness);

        $this->cookieSuggestionRepository->save($cookieSuggestion);
    }
}
