<?php

declare(strict_types=1);

namespace App\Web\AdminModule\CrawlerModule\Control\DeleteScenarioScheduler;

use App\Web\Ui\Modal\AbstractModalTemplate;
use SixtyEightPublishers\CrawlerClient\Controller\ScenarioScheduler\ScenarioSchedulerResponse;
use Throwable;

final class DeleteScenarioSchedulerModalTemplate extends AbstractModalTemplate
{
    public ?string $scenarioSchedulerId = null;

    public ?ScenarioSchedulerResponse $scenarioSchedulerResponse = null;

    public ?Throwable $responseError = null;
}
