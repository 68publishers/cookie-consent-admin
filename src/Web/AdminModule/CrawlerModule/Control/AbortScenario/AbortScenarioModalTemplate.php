<?php

declare(strict_types=1);

namespace App\Web\AdminModule\CrawlerModule\Control\AbortScenario;

use App\Web\Ui\Modal\AbstractModalTemplate;
use SixtyEightPublishers\CrawlerClient\Controller\Scenario\ScenarioResponse;
use Throwable;

final class AbortScenarioModalTemplate extends AbstractModalTemplate
{
    public ?string $scenarioId = null;

    public ?ScenarioResponse $scenarioResponse = null;

    public ?Throwable $responseError = null;
}
