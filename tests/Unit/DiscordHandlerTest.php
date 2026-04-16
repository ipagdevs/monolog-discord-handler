<?php

use Monolog\Level;
use Monolog\LogRecord;
use GuzzleHttp\ClientInterface;
use IpagDevs\Logging\DiscordConfig;
use IpagDevs\Logging\DiscordHandler;
use Psr\Http\Message\ResponseInterface;

function makeHandlerRecord(
    string $message = 'Test message',
    Level $level = Level::Info,
    string $channel = 'test',
    array $context = [],
): LogRecord {
    return new LogRecord(
        datetime: new DateTimeImmutable(),
        channel: $channel,
        level: $level,
        message: $message,
        context: $context,
        extra: [],
    );
}

describe('DiscordHandler constructor', function () {
    it('creates a DiscordConfig with the webhook URL', function () {
        $handler = new DiscordHandler('https://discord.com/api/webhooks/abc');

        expect($handler->getConfig()->getWebhookUrl())->toBe('https://discord.com/api/webhooks/abc');
    });

    it('defaults to Debug level', function () {
        $handler = new DiscordHandler('https://discord.com/api/webhooks/abc');

        expect($handler->getLevel())->toBe(Level::Debug);
    });

    it('accepts a custom log level', function () {
        $handler = new DiscordHandler('https://discord.com/api/webhooks/abc', Level::Error);

        expect($handler->getLevel())->toBe(Level::Error);
    });
});

describe('DiscordHandler::getConfig / withConfig', function () {
    it('returns the current config', function () {
        $handler = new DiscordHandler('https://discord.com/api/webhooks/abc');

        expect($handler->getConfig())->toBeInstanceOf(DiscordConfig::class);
    });

    it('replaces the config via withConfig', function () {
        $handler = new DiscordHandler('https://discord.com/api/webhooks/abc');
        $newConfig = new DiscordConfig('https://discord.com/api/webhooks/new');

        $handler->withConfig($newConfig);

        expect($handler->getConfig())->toBe($newConfig);
    });

    it('returns self from withConfig for fluent chaining', function () {
        $handler = new DiscordHandler('https://discord.com/api/webhooks/abc');
        $newConfig = new DiscordConfig('https://discord.com/api/webhooks/new');

        expect($handler->withConfig($newConfig))->toBe($handler);
    });
});

describe('DiscordHandler::getClass', function () {
    it('returns the fully qualified class name', function () {
        $handler = new DiscordHandler('https://discord.com/api/webhooks/abc');

        expect($handler->getClass())->toBe(DiscordHandler::class);
    });
});

describe('DiscordHandler::handle (write)', function () {
    it('sends a POST request to the webhook URL when handling a log record', function () {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockClient = $this->createMock(ClientInterface::class);

        $mockClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://discord.com/api/webhooks/abc', $this->callback(function ($options) {
                return is_array($options) && isset($options['json']);
            }))->willReturn($mockResponse);

        $config = (new DiscordConfig('https://discord.com/api/webhooks/abc'))
            ->withHttpClient($mockClient);

        $handler = (new DiscordHandler('https://discord.com/api/webhooks/abc'))
            ->withConfig($config);

        $handler->handle(makeHandlerRecord('Hello Discord', Level::Error));
    });

    it('does not propagate exceptions thrown by the HTTP client', function () {
        $mockClient = $this->createMock(ClientInterface::class);

        $mockClient->method('request')
            ->willThrowException(new RuntimeException('Connection refused'));

        $config = (new DiscordConfig('https://discord.com/api/webhooks/abc'))
            ->withHttpClient($mockClient);

        $handler = (new DiscordHandler('https://discord.com/api/webhooks/abc'))
            ->withConfig($config);

        // Should not throw
        expect(fn() => $handler->handle(makeHandlerRecord()))->not->toThrow(RuntimeException::class);
    });

    it('respects the configured log level and skips records below it', function () {
        $mockClient = $this->createMock(ClientInterface::class);

        $mockClient->expects($this->never())
            ->method('request');

        $config = (new DiscordConfig('https://discord.com/api/webhooks/abc'))
            ->withHttpClient($mockClient);

        $handler = (new DiscordHandler('https://discord.com/api/webhooks/abc', Level::Error))
            ->withConfig($config);

        $handler->handle(makeHandlerRecord('Low priority', Level::Debug));
    });

    it('sends the payload with context fields', function () {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockClient = $this->createMock(ClientInterface::class);

        $mockClient->expects($this->once())
            ->method('request')
            ->with('POST', $this->anything(), $this->callback(function (array $options) {
                $fields = $options['json']['embeds'][0]['fields'] ?? [];
                $names = array_column($fields, 'name');
                return in_array(ucfirst('user_id'), $names);
            }))
            ->willReturn($mockResponse);

        $config = (new DiscordConfig('https://discord.com/api/webhooks/abc'))
            ->withHttpClient($mockClient);

        $handler = (new DiscordHandler('https://discord.com/api/webhooks/abc'))
            ->withConfig($config);

        $handler->handle(makeHandlerRecord(context: ['user_id' => 99]));
    });

    it('sends plain content when embedded is disabled', function () {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockClient = $this->createMock(ClientInterface::class);

        $mockClient->expects($this->once())
            ->method('request')
            ->with('POST', $this->anything(), $this->callback(function (array $options) {
                return isset($options['json']['content']) && !isset($options['json']['embeds']);
            }))
            ->willReturn($mockResponse);

        $config = (new DiscordConfig('https://discord.com/api/webhooks/abc'))
            ->withHttpClient($mockClient)
            ->withEmbedded(false);

        $handler = (new DiscordHandler('https://discord.com/api/webhooks/abc'))
            ->withConfig($config);

        $handler->handle(makeHandlerRecord('Plain message'));
    });
});
