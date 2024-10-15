<?php

declare(strict_types=1);

namespace App\Application\Cookie;

final class Template
{
    public const string OPTION_NO_CACHE = 'no_cache';

    private string $projectId;

    private string $template;

    private TemplateArguments $arguments;

    private array $options = [];

    private function __construct() {}

    public static function create(string $projectId, string $template, TemplateArguments $arguments): self
    {
        $cookieTemplate = new self();
        $cookieTemplate->projectId = $projectId;
        $cookieTemplate->template = $template;
        $cookieTemplate->arguments = $arguments;

        return $cookieTemplate;
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function template(): string
    {
        return $this->template;
    }

    public function arguments(): TemplateArguments
    {
        return $this->arguments;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options()[$key] ?? $default;
    }

    public function withOption(string $key, mixed $value): self
    {
        $cookieTemplate = clone $this;
        $cookieTemplate->options[$key] = $value;

        return $cookieTemplate;
    }
}
