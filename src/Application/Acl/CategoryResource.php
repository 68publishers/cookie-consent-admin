<?php

declare(strict_types=1);

namespace App\Application\Acl;

final class CategoryResource extends AbstractResource
{
    public const string READ = 'read';
    public const string CREATE = 'create';
    public const string UPDATE = 'update';
    public const string DELETE = 'delete';
}
