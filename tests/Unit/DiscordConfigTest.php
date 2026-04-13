<?php

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use IpagDevs\Logging\DiscordConfig;

describe('DiscordConfig', function () {
    it('stores the webhook URL', function () {
        $config = new DiscordConfig('https://discord.com/api/webhooks/test');

        expect($config->getWebhookUrl())->toBe('https://discord.com/api/webhooks/test');
    });

    it('defaults embedded to true', function () {
        $config = new DiscordConfig('https://discord.com/api/webhooks/test');

        expect($config->isEmbedded())->toBeTrue();
    });

    it('sets embedded to false via withEmbedded', function () {
        $config = (new DiscordConfig('https://discord.com/api/webhooks/test'))
            ->withEmbedded(false);

        expect($config->isEmbedded())->toBeFalse();
    });

    it('returns self from withEmbedded for fluent chaining', function () {
        $config = new DiscordConfig('https://discord.com/api/webhooks/test');

        expect($config->withEmbedded(false))->toBe($config);
    });

    it('creates a default GuzzleHttp Client when no client is set', function () {
        $config = new DiscordConfig('https://discord.com/api/webhooks/test');

        expect($config->getHttpClient())->toBeInstanceOf(Client::class);
    });

    it('always returns the same default client instance', function () {
        $config = new DiscordConfig('https://discord.com/api/webhooks/test');

        expect($config->getHttpClient())->toBe($config->getHttpClient());
    });

    it('accepts a custom HTTP client via withHttpClient', function () {
        $mockClient = $this->createMock(ClientInterface::class);

        $config = (new DiscordConfig('https://discord.com/api/webhooks/test'))
            ->withHttpClient($mockClient);

        expect($config->getHttpClient())->toBe($mockClient);
    });

    it('returns self from withHttpClient for fluent chaining', function () {
        $mockClient = $this->createMock(ClientInterface::class);
        $config = new DiscordConfig('https://discord.com/api/webhooks/test');

        expect($config->withHttpClient($mockClient))->toBe($config);
    });
});
