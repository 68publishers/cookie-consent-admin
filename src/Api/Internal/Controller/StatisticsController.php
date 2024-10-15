<?php

declare(strict_types=1);

namespace App\Api\Internal\Controller;

use Apitte\Core\Annotation\Controller as Api;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Internal\RequestBody\GetProjectStatisticsRequestBody;
use App\Application\Statistics\Period;
use App\Application\Statistics\ProjectStatisticsCalculatorInterface;
use App\ReadModel\Project\FindProjectsAccessibilityByCodeQuery;
use App\ReadModel\Project\ProjectPermissionView;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

/**
 * @Api\Path("/statistics")
 */
final class StatisticsController extends AbstractInternalController
{
    public const string ENDPOINT_PROJECTS = '/api/internal/statistics/projects';

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ProjectStatisticsCalculatorInterface $projectStatisticsCalculator,
    ) {}

    /**
     * @Api\Path("/projects")
     * @Api\Method("GET")
     * @Api\RequestBody(entity="App\Api\Internal\RequestBody\GetProjectStatisticsRequestBody", required=true)
     */
    public function getProjectStatistics(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $requestEntity = $request->getEntity();
        assert($requestEntity instanceof GetProjectStatisticsRequestBody);

        $timezone = new DateTimeZone($requestEntity->timezone);
        $projectCodes = (array) $requestEntity->projects;
        $projectIds = array_fill_keys($projectCodes, null);
        $inaccessible = [];

        // check accessibility for requested projects
        foreach ($this->queryBus->dispatch(FindProjectsAccessibilityByCodeQuery::create($requestEntity->userId, $projectCodes)) as $projectPermissionView) {
            assert($projectPermissionView instanceof ProjectPermissionView);

            $projectIds[$projectPermissionView->projectCode->value()] = $projectPermissionView->projectId->toString();

            if (!$projectPermissionView->permission) {
                $inaccessible[] = $projectPermissionView->projectCode->value();
            }
        }

        $projectIds = array_filter($projectIds);

        // are some projects inaccessible for the current user?
        if (0 < count($inaccessible)) {
            return $response->withStatus(ApiResponse::S401_UNAUTHORIZED)
                ->writeJsonBody([
                    'status' => 'error',
                    'data' => [
                        'code' => ApiResponse::S401_UNAUTHORIZED,
                        'error' => sprintf(
                            'Project%s %s are not accessible for the user.',
                            1 < count($inaccessible) ? 's' : '',
                            implode(', ', $inaccessible),
                        ),
                    ],
                ]);
        }

        try {
            [$startDate, $endDate] = $this->createRange($requestEntity, $timezone);
        } catch (Exception $e) {
            return $response->withStatus(ApiResponse::S422_UNPROCESSABLE_ENTITY)
                ->writeJsonBody([
                    'status' => 'error',
                    'data' => [
                        'code' => ApiResponse::S422_UNPROCESSABLE_ENTITY,
                        'error' => 'Invalid date format.',
                    ],
                ]);
        }

        return $response->withStatus(ApiResponse::S200_OK)
            ->writeJsonBody([
                'status' => 'success',
                'data' => $this->buildData($projectIds, $requestEntity->locale, $startDate, $endDate, $timezone, $requestEntity->environment),
            ]);
    }

    /**
     * @param array<string> $projectIdsByCodes
     */
    private function buildData(array $projectIdsByCodes, string $locale, DateTimeImmutable $startDate, DateTimeImmutable $endDate, DateTimeZone $userTz, ?string $environment): array
    {
        $data = [];

        if (0 >= count($projectIdsByCodes)) {
            return $data;
        }

        $period = Period::create($startDate, $endDate);

        foreach ($projectIdsByCodes as $code => $projectId) {
            $consentStatistics = $this->projectStatisticsCalculator->calculateConsentStatistics(
                projectId: $projectId,
                currentPeriod: $period,
                environment: $environment,
            );
            $cookieStatistics = $this->projectStatisticsCalculator->calculateCookieStatistics(
                projectId: $projectId,
                endDate: $endDate,
                environment: $environment,
            );
            $lastConsentDate = $this->projectStatisticsCalculator->calculateLastConsentDate(
                projectId: $projectId,
                endDate: $endDate,
                environment: $environment,
            );
            $cookieSuggestionStatistics = $this->projectStatisticsCalculator->calculateCookieSuggestionStatistics(
                projectId: $projectId,
            );

            if (null !== $lastConsentDate) {
                $lastConsentDate = $lastConsentDate->setTimezone($userTz);
            }

            $data[$code] = [
                'allConsents' => [
                    'value' => $consentStatistics->totalConsentsStatistics()->currentValue(),
                    'percentageDiff' => $consentStatistics->totalConsentsStatistics()->percentageDiff(),
                ],
                'uniqueConsents' => [
                    'value' => $consentStatistics->uniqueConsentsStatistics()->currentValue(),
                    'percentageDiff' => $consentStatistics->uniqueConsentsStatistics()->percentageDiff(),
                ],
                'allPositive' => [
                    'value' => $consentStatistics->totalConsentsPositivityStatistics()->currentValue(),
                    'percentageDiff' => $consentStatistics->totalConsentsPositivityStatistics()->percentageDiff(),
                ],
                'uniquePositive' => [
                    'value' => $consentStatistics->uniqueConsentsPositivityStatistics()->currentValue(),
                    'percentageDiff' => $consentStatistics->uniqueConsentsPositivityStatistics()->percentageDiff(),
                ],
                'lastConsent' => [
                    'value' => $lastConsentDate?->format(DateTimeInterface::ATOM),
                    'formattedValue' => $lastConsentDate?->format('j.n.Y H:i:s'),
                    'text' => null !== $lastConsentDate ? Carbon::parse($lastConsentDate)->locale($locale)->ago(CarbonInterface::DIFF_RELATIVE_TO_NOW) : null,
                ],
                'providers' => [
                    'value' => $cookieStatistics->numberOfProviders(),
                ],
                'cookies' => [
                    'commonValue' => $cookieStatistics->numberOfCommonCookies(),
                    'privateValue' => $cookieStatistics->numberOfPrivateCookies(),
                ],
                'cookieSuggestions' => [
                    'enabled' => null !== $cookieSuggestionStatistics,
                    'missing' => null !== $cookieSuggestionStatistics ? $cookieSuggestionStatistics->missing : 0,
                    'unassociated' => null !== $cookieSuggestionStatistics ? $cookieSuggestionStatistics->unassociated : 0,
                    'problematic' => null !== $cookieSuggestionStatistics ? $cookieSuggestionStatistics->problematic : 0,
                    'unproblematic' => null !== $cookieSuggestionStatistics ? $cookieSuggestionStatistics->unproblematic : 0,
                    'ignored' => null !== $cookieSuggestionStatistics ? $cookieSuggestionStatistics->ignored : 0,
                ],
            ];
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    private function createRange(GetProjectStatisticsRequestBody $requestBody, DateTimeZone $userTz): array
    {
        $utc = new DateTimeZone('UTC');

        $startDate = (new DateTimeImmutable($requestBody->startDate, $userTz))
            ->setTime(0, 0)
            ->setTimezone($utc);

        $endDate = (new DateTimeImmutable($requestBody->endDate, $userTz))
            ->setTime(23, 59, 59)
            ->setTimezone($utc);

        return $startDate > $endDate ? [$endDate, $startDate] : [$startDate, $endDate];
    }
}
