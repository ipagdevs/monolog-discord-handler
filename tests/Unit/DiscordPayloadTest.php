<?php

use Monolog\Level;
use Monolog\LogRecord;
use IpagDevs\Logging\DiscordField;
use IpagDevs\Logging\DiscordConfig;
use IpagDevs\Logging\DiscordLimits;
use IpagDevs\Logging\DiscordPayload;

function makeRecord(
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

function makeConfig(bool $embedded = true): DiscordConfig
{
    return (new DiscordConfig('https://discord.com/api/webhooks/test'))
        ->withEmbedded($embedded);
}

describe('DiscordPayload::from', function () {
    it('uses the level name as the embed title', function () {
        $record = makeRecord(level: Level::Warning);
        $payload = DiscordPayload::from(makeConfig(), $record);

        $data = $payload->jsonSerialize();

        expect($data['embeds'][0]['title'])->toBe('WARNING');
    });

    it('uses the log message as the embed description', function () {
        $record = makeRecord('Something happened');
        $payload = DiscordPayload::from(makeConfig(), $record);

        $data = $payload->jsonSerialize();

        expect($data['embeds'][0]['description'])->toBe('Something happened');
    });

    it('truncates a long message to DESCRIPTION limit', function () {
        $long = str_repeat('m', 5000);
        $record = makeRecord($long);
        $payload = DiscordPayload::from(makeConfig(), $record);

        $data = $payload->jsonSerialize();

        expect(mb_strlen($data['embeds'][0]['description']))->toBe(DiscordLimits::DESCRIPTION)
            ->and($data['embeds'][0]['description'])->toEndWith('...');
    });

    it('adds context entries as fields', function () {
        $record = makeRecord(context: ['user_id' => 42, 'action' => 'login']);
        $payload = DiscordPayload::from(makeConfig(), $record);

        $data = $payload->jsonSerialize();
        $fieldNames = array_column($data['embeds'][0]['fields'], 'name');

        expect($fieldNames)->toContain('user_id')
            ->and($fieldNames)->toContain('action');
    });

    it('always includes channel and timestamp fields', function () {
        $record = makeRecord(channel: 'app');
        $payload = DiscordPayload::from(makeConfig(), $record);

        $data = $payload->jsonSerialize();
        $fieldNames = array_column($data['embeds'][0]['fields'], 'name');

        expect($fieldNames)->toContain('channel')
            ->and($fieldNames)->toContain('timestamp');
    });

    it('sets correct color for each log level', function (Level $level, int $expectedColor) {
        $record = makeRecord(level: $level);
        $payload = DiscordPayload::from(makeConfig(), $record);

        $data = $payload->jsonSerialize();

        expect($data['embeds'][0]['color'])->toBe($expectedColor);
    })->with([
        'debug'     => [Level::Debug, 0x95A5A6],
        'info'      => [Level::Info, 0x3498DB],
        'notice'    => [Level::Notice, 0x1ABC9C],
        'warning'   => [Level::Warning, 0xF1C40F],
        'error'     => [Level::Error, 0xE74C3C],
        'critical'  => [Level::Critical, 0x992D22],
        'alert'     => [Level::Alert, 0xE67E22],
        'emergency' => [Level::Emergency, 0x992D22],
    ]);
});

describe('DiscordPayload::addField', function () {
    it('does not add fields beyond MAX_FIELDS', function () {
        $record = makeRecord();
        $payload = DiscordPayload::from(makeConfig(), $record);

        // Fill fields up to the limit
        for ($i = 0; $i < DiscordLimits::MAX_FIELDS + 5; $i++) {
            $payload->addField(DiscordField::create("field_{$i}", "value_{$i}"));
        }

        $data = $payload->jsonSerialize();

        expect($data['embeds'][0]['fields'])->toHaveCount(DiscordLimits::MAX_FIELDS);
    });
});

describe('DiscordPayload::jsonSerialize (embedded mode)', function () {
    it('returns embeds array structure', function () {
        $payload = DiscordPayload::from(makeConfig(embedded: true), makeRecord());
        $data = $payload->jsonSerialize();

        expect($data)->toHaveKey('embeds')
            ->and($data['embeds'])->toHaveCount(1)
            ->and($data['embeds'][0])->toHaveKeys(['title', 'description', 'color', 'timestamp', 'fields']);
    });

    it('includes the record datetime as timestamp', function () {
        $datetime = new DateTimeImmutable('2025-01-15T12:00:00+00:00');
        $record = new LogRecord(
            datetime: $datetime,
            channel: 'test',
            level: Level::Info,
            message: 'msg',
        );

        $payload = DiscordPayload::from(makeConfig(), $record);
        $data = $payload->jsonSerialize();

        expect($data['embeds'][0]['timestamp'])->toBe($datetime->format('c'));
    });
});

describe('DiscordPayload::jsonSerialize (non-embedded mode)', function () {
    it('returns a content string instead of embeds', function () {
        $record = makeRecord('A plain log message', Level::Error);
        $payload = DiscordPayload::from(makeConfig(embedded: false), $record);
        $data = $payload->jsonSerialize();

        expect($data)->toHaveKey('content')
            ->and($data)->not->toHaveKey('embeds')
            ->and($data['content'])->toContain('ERROR')
            ->and($data['content'])->toContain('A plain log message');
    });
});
