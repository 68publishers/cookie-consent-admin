<?php

declare(strict_types=1);

namespace App\Application\Acl;

final class ProjectResource extends AbstractResource
{
    public const string READ = 'read';
    public const string READ_ALL = 'read_all';
    public const string CREATE = 'create';
    public const string UPDATE = 'update';
    public const string DELETE = 'delete';
    public const string EXPORT = 'export';
}
