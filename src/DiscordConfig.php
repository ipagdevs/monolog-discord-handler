<?php

namespace IpagDevs\Logging;

use GuzzleHttp\ClientInterface;

class DiscordConfig
{
    private ?ClientInterface $http = null;
    private bool $embedded = true;

    public function __construct(private readonly string $webhookUrl) {}

    public function withHttpClient(ClientInterface $http): self
    {
        $this->http = $http;
        return $this;
    }

    public function withEmbedded(bool $embedded): self
    {
        $this->embedded = $embedded;
        return $this;
    }

    public function isEmbedded(): bool
    {
        return $this->embedded;
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->http ??= new \GuzzleHttp\Client([
            'timeout' => 10.0,
        ]);
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }
}
