<?php

use IpagDevs\Logging\DiscordLimits;

describe('DiscordLimits::truncate', function () {
    it('returns the string unchanged when within the limit', function () {
        $value = str_repeat('a', 100);

        expect(DiscordLimits::truncate($value, 256))->toBe($value);
    });

    it('returns the string unchanged when exactly at the limit', function () {
        $value = str_repeat('a', 256);

        expect(DiscordLimits::truncate($value, 256))->toBe($value);
    });

    it('truncates the string and appends ellipsis when over the limit', function () {
        $value = str_repeat('a', 260);

        $result = DiscordLimits::truncate($value, 256);

        expect($result)->toHaveLength(256)
            ->and($result)->toEndWith('...');
    });

    it('handles multibyte characters correctly', function () {
        $value = str_repeat('é', 260);

        $result = DiscordLimits::truncate($value, 256);

        expect(mb_strlen($result))->toBe(256)
            ->and($result)->toEndWith('...');
    });

    it('applies truncation using the MESSAGE constant', function () {
        $long = str_repeat('x', 3000);

        $result = DiscordLimits::truncate($long, DiscordLimits::MESSAGE);

        expect(mb_strlen($result))->toBe(DiscordLimits::MESSAGE)
            ->and($result)->toEndWith('...');
    });
});

describe('DiscordLimits::slice', function () {
    it('returns array unchanged when below MAX_FIELDS', function () {
        $array = range(1, 10);

        expect(DiscordLimits::slice($array))->toBe($array);
    });

    it('returns array unchanged when exactly at MAX_FIELDS', function () {
        $array = range(1, DiscordLimits::MAX_FIELDS);

        expect(DiscordLimits::slice($array))->toBe($array);
    });

    it('slices array to MAX_FIELDS when over the limit', function () {
        $array = range(1, 30);

        $result = DiscordLimits::slice($array);

        expect($result)->toHaveCount(DiscordLimits::MAX_FIELDS)
            ->and($result)->toBe(range(1, DiscordLimits::MAX_FIELDS));
    });
});

describe('DiscordLimits constants', function () {
    it('defines expected Discord API limits', function () {
        expect(DiscordLimits::FIELD_NAME)->toBe(256)
            ->and(DiscordLimits::FIELD_VALUE)->toBe(1024)
            ->and(DiscordLimits::MESSAGE)->toBe(500)
            ->and(DiscordLimits::DESCRIPTION)->toBe(4096)
            ->and(DiscordLimits::MAX_FIELDS)->toBe(25);
    });
});
