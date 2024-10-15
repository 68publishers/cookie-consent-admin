<?php

declare(strict_types=1);

namespace App\Application\Statistics;

use App\Application\GlobalSettings\GlobalSettingsInterface;
use App\ReadModel\Consent\CalculateConsentStatisticsPerPeriodQuery;
use App\ReadModel\Consent\CalculateLastConsentDateQuery;
use App\ReadModel\Consent\ConsentStatisticsView;
use App\ReadModel\Project\CalculateProjectCookieTotalsQuery;
use App\ReadModel\Project\GetProjectCookieSuggestionStatisticsQuery;
use App\ReadModel\Project\ProjectCookieSuggestionsStatistics;
use App\ReadModel\Project\ProjectCookieTotalsView;
use DateTimeImmutable;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class ProjectStatisticsCalculator implements ProjectStatisticsCalculatorInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private GlobalSettingsInterface $globalSettings,
    ) {}

    public function calculateConsentStatistics(string $projectId, Period $currentPeriod, ?Period $previousPeriod = null, ?string $environment = null): ConsentStatistics
    {
        $previousPeriod = $previousPeriod ?? $currentPeriod->createPreviousPeriod();
        $previousStatisticsQuery = CalculateConsentStatisticsPerPeriodQuery::create(
            projectId: $projectId,
            startDate: $previousPeriod->startDate(),
            endDate: $previousPeriod->endDate(),
            environment: $environment,
        );
        $currentStatisticsQuery = CalculateConsentStatisticsPerPeriodQuery::create(
            projectId: $projectId,
            startDate: $currentPeriod->startDate(),
            endDate: $currentPeriod->endDate(),
            environment: $environment,
        );

        $previousStatistics = $this->queryBus->dispatch($previousStatisticsQuery);
        $currentStatistics = $this->queryBus->dispatch($currentStatisticsQuery);

        assert($previousStatistics instanceof ConsentStatisticsView);
        assert($currentStatistics instanceof ConsentStatisticsView);

        $previousTotalPositivitySum = $previousStatistics->totalPositiveCount + $previousStatistics->totalNegativeCount;
        $previousUniquePositivitySum = $previousStatistics->uniquePositiveCount + $previousStatistics->uniqueNegativeCount;

        $currentTotalPositivitySum = $currentStatistics->totalPositiveCount + $currentStatistics->totalNegativeCount;
        $currentUniquePositivitySum = $currentStatistics->uniquePositiveCount + $currentStatistics->uniqueNegativeCount;

        return ConsentStatistics::create(
            PeriodStatistics::create($previousStatistics->totalConsentsCount, $currentStatistics->totalConsentsCount),
            PeriodStatistics::create($previousStatistics->uniqueConsentsCount, $currentStatistics->uniqueConsentsCount),
            PeriodStatistics::create(
                (int) round(0 === $previousTotalPositivitySum ? 0 : $previousStatistics->totalPositiveCount / $previousTotalPositivitySum * 100),
                (int) round(0 === $currentTotalPositivitySum ? 0 : $currentStatistics->totalPositiveCount / $currentTotalPositivitySum * 100),
            ),
            PeriodStatistics::create(
                (int) round(0 === $previousUniquePositivitySum ? 0 : $previousStatistics->uniquePositiveCount / $previousUniquePositivitySum * 100),
                (int) round(0 === $currentUniquePositivitySum ? 0 : $currentStatistics->uniquePositiveCount / $currentUniquePositivitySum * 100),
            ),
        );
    }

    public function calculateCookieStatistics(string $projectId, DateTimeImmutable $endDate, ?string $environment = null): CookieStatistics
    {
        $query = CalculateProjectCookieTotalsQuery::create(
            projectId: $projectId,
            maxDate: $endDate,
            environment: $environment,
        );

        $totals = $this->queryBus->dispatch($query);
        assert($totals instanceof ProjectCookieTotalsView);

        return CookieStatistics::create(
            $totals->providers,
            $totals->commonCookies,
            $totals->privateCookies,
        );
    }

    public function calculateLastConsentDate(string $projectId, DateTimeImmutable $endDate, ?string $environment = null): ?DateTimeImmutable
    {
        $query = CalculateLastConsentDateQuery::create(
            projectId: $projectId,
            maxDate: $endDate,
            environment: $environment,
        );

        return $this->queryBus->dispatch($query);
    }

    public function calculateCookieSuggestionStatistics(string $projectId): ?ProjectCookieSuggestionsStatistics
    {
        if (!$this->globalSettings->crawlerSettings()->enabled()) {
            return null;
        }

        $statistics = $this->queryBus->dispatch(GetProjectCookieSuggestionStatisticsQuery::create($projectId));

        if (!$statistics instanceof ProjectCookieSuggestionsStatistics) {
            return null;
        }

        return 0 < $statistics->totalWithoutVirtual ? $statistics : null;
    }
}
