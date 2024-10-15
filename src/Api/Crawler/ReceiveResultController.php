<?php

declare(strict_types=1);

namespace App\Api\Crawler;

use Apitte\Core\Annotation\Controller as Api;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Application\CookieSuggestion\CookieSuggestionsStoreInterface;
use App\Application\Crawler\CrawlerClientProvider;
use App\Application\GlobalSettings\GlobalSettingsInterface;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use SixtyEightPublishers\CrawlerClient\Controller\Scenario\ResponseBody\ScenarioResponseBody;
use Throwable;

/**
 * @Api\Path("/receive-result")
 */
final class ReceiveResultController extends AbstractCrawlerController
{
    public function __construct(
        private readonly GlobalSettingsInterface $globalSettings,
        private readonly CrawlerClientProvider $crawlerClientProvider,
        private readonly CookieSuggestionsStoreInterface $cookieSuggestionsStore,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @Api\Path("/")
     * @Api\Method("POST")
     *
     * @throws Exception
     */
    public function receiveResult(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $authenticationResponse = $this->doAuthentication($request, $response);

        if (null !== $authenticationResponse) {
            return $authenticationResponse;
        }

        try {
            $scenarioResponseBody = $this->crawlerClientProvider->get()
                ->getSerializer()
                ->deserialize($request->getBody()->getContents(), ScenarioResponseBody::class);

            assert($scenarioResponseBody instanceof ScenarioResponseBody);
        } catch (Throwable $e) {
            $error = new ClientErrorException(sprintf(
                'Unable to deserialize received crawler result: %s',
                $e->getMessage(),
            ), ApiResponse::S400_BAD_REQUEST, $e);

            $this->logger->error((string) $error);

            throw $error;
        }

        if (!isset($scenarioResponseBody->flags['projectId'])) {
            $error = new ClientErrorException('Unable to process received crawler result: Missing flag "projectId".', ApiResponse::S400_BAD_REQUEST);

            $this->logger->error((string) $error);

            throw $error;
        }

        $acceptedCategories = [];

        foreach ($scenarioResponseBody->flags as $flagName => $flagValue) {
            if (str_starts_with($flagName, 'category.') && '1' === $flagValue) {
                $acceptedCategories[] = substr($flagName, 9);
            }
        }

        try {
            $this->cookieSuggestionsStore->storeCrawledCookies(
                $scenarioResponseBody->name,
                $scenarioResponseBody->flags['projectId'],
                $acceptedCategories,
                $scenarioResponseBody->finishedAt ?? new DateTimeImmutable('now'),
                $scenarioResponseBody->results->cookies,
            );
        } catch (Throwable $e) {
            $error = new ClientErrorException('Unable to process received crawler result: ' . $e->getMessage(), ApiResponse::S400_BAD_REQUEST, $e);

            $this->logger->error((string) $error);

            throw $error;
        }

        return $response->withStatus(ApiResponse::S200_OK)
            ->writeJsonBody([
                'status' => 'success',
                'data' => [],
            ]);
    }

    private function doAuthentication(ApiRequest $request, ApiResponse $response): ?ApiResponse
    {
        $authorizationHeader = $request->getHeader('Authorization')[0] ?? '';

        if (empty($authorizationHeader)) {
            return $response->withStatus(ApiResponse::S401_UNAUTHORIZED)
                ->writeJsonBody([
                    'status' => 'error',
                    'data' => [
                        'code' => ApiResponse::S401_UNAUTHORIZED,
                        'error' => 'Missing Authorization header',
                    ],
                ]);
        }

        $methodAndToken = explode(' ', $authorizationHeader, 2);
        $credentials = 2 === count($methodAndToken) ? explode(':', (string) base64_decode($methodAndToken[1]), 2) : [];

        if ('Basic' !== ($methodAndToken[0] ?? '') || 2 !== count($credentials)) {
            return $response->withStatus(ApiResponse::S401_UNAUTHORIZED)
                ->writeJsonBody([
                    'status' => 'error',
                    'data' => [
                        'code' => ApiResponse::S401_UNAUTHORIZED,
                        'error' => 'Malformed Authorization header',
                    ],
                ]);
        }

        $crawlerSettings = $this->globalSettings->crawlerSettings();
        [$username, $callbackUriToken] = $credentials;

        if (false === $crawlerSettings->enabled() || $username !== $crawlerSettings->username() || $callbackUriToken !== $crawlerSettings->callbackUriToken()) {
            return $response->withStatus(ApiResponse::S401_UNAUTHORIZED)
                ->writeJsonBody([
                    'status' => 'error',
                    'data' => [
                        'code' => ApiResponse::S401_UNAUTHORIZED,
                        'error' => 'Unauthorized',
                    ],
                ]);
        }

        return null;
    }
}
