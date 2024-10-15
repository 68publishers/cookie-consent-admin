<?php

declare(strict_types=1);

namespace App\ReadModel\Cookie;

final class CookieDataForSuggestion
{
    public const string METADATA_FIELD_SAME_DOMAIN = 'same_domain';

    /** @var array<string, mixed> */
    private array $metadata = [];

    public function __construct(
        public string $id,
        public string $name,
        public string $domain,
        public string $projectDomain,
        public string $categoryId,
        public string $categoryCode,
        public string $providerId,
        public string $providerCode,
        public string $providerName,
        public bool $associated,
    ) {}

    public function withMetadataField(string $key, $value): self
    {
        $cookie = clone $this;
        $cookie->metadata[$key] = $value;

        return $cookie;
    }

    public function getMetadataField(string $key)
    {
        return $this->metadata[$key] ?? null;
    }
}
