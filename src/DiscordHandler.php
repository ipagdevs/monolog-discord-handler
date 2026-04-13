<?php

namespace IpagDevs\Logging;

use Throwable;
use Monolog\Level;
use Monolog\LogRecord;

class DiscordHandler extends \Monolog\Handler\AbstractProcessingHandler
{
    private DiscordConfig $config;

    public function __construct(
        private readonly string $webhook_url,
        int|string|Level $level = Level::Debug
    ) {
        parent::__construct($level);

        $this->config = (new DiscordConfig($this->webhook_url))
            ->withHttpClient(new \GuzzleHttp\Client([
                'timeout' => 10.0,
            ]));
    }

    protected function write(LogRecord $record): void
    {
        try {
            $payload = DiscordPayload::from(
                $this->config,
                $record
            );

            $this->send($payload);
        } catch (Throwable) {
            // Ignore exceptions thrown by the HTTP client to avoid breaking the application
        }
    }

    protected function send(DiscordPayload $payload): void
    {
        try {
            $this->config->getHttpClient()->request('POST', $this->config->getWebhookUrl(), ['json' => $payload->jsonSerialize()]);
        } catch (Throwable) {
            // Ignore exceptions thrown by the HTTP client to avoid breaking the application
        }
    }

    public function getConfig(): DiscordConfig
    {
        return $this->config;
    }

    public function withConfig(DiscordConfig $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function getClass(): string
    {
        return self::class;
    }
}
