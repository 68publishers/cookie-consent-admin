<?php

declare(strict_types=1);

namespace App\Application\Acl;

final class CrawlerScenariosResource extends AbstractResource
{
    public const string READ = 'read';
    public const string RUN = 'run';
    public const string ABORT = 'abort';
}
