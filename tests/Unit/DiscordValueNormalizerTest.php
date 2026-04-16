<?php

use IpagDevs\Logging\DiscordLimits;
use IpagDevs\Logging\DiscordValueNormalizer;

describe('DiscordValueNormalizer::normalize', function () {
    it('normalizes a string scalar', function () {
        expect(DiscordValueNormalizer::normalize('hello'))->toBe('hello');
    });

    it('normalizes an integer scalar', function () {
        expect(DiscordValueNormalizer::normalize(42))->toBe('42');
    });

    it('normalizes a float scalar', function () {
        expect(DiscordValueNormalizer::normalize(3.14))->toBe('3.14');
    });

    it('normalizes true as "true"', function () {
        expect(DiscordValueNormalizer::normalize(true))->toBe('true');
    });

    it('normalizes false as "false"', function () {
        expect(DiscordValueNormalizer::normalize(false))->toBe('false');
    });

    it('normalizes null as "null"', function () {
        expect(DiscordValueNormalizer::normalize(null))->toBe('null');
    });

    it('normalizes an array as pretty JSON', function () {
        $result = DiscordValueNormalizer::normalize(['key' => 'value']);

        expect($result)->toContain('"key"')
            ->and($result)->toContain('"value"');
    });

    it('normalizes an object as pretty JSON', function () {
        $obj = new stdClass();
        $obj->name = 'test';

        $result = DiscordValueNormalizer::normalize($obj);

        expect($result)->toContain('"name"')
            ->and($result)->toContain('"test"');
    });

    it('truncates a long scalar string to FIELD_VALUE limit', function () {
        $long = str_repeat('a', 2000);

        $result = DiscordValueNormalizer::normalize($long);

        expect(mb_strlen($result))->toBeLessThanOrEqual(DiscordLimits::FIELD_VALUE)
            ->and($result)->toEndWith('...');
    });

    it('truncates long JSON to FIELD_VALUE limit', function () {
        $large = array_fill(0, 200, str_repeat('x', 50));

        $result = DiscordValueNormalizer::normalize($large);

        expect(mb_strlen($result))->toBeLessThanOrEqual(DiscordLimits::FIELD_VALUE);
    });

    it('normalizes a Closure as callable[closure]', function () {
        $closure = function () {};

        expect(DiscordValueNormalizer::normalize($closure))->toBe('callable[closure]');
    });

    it('normalizes a string callable as callable:functionName', function () {
        expect(DiscordValueNormalizer::normalize('strlen'))->toBe('callable:strlen');
    });

    it('normalizes an invokable object as callable[ClassName]', function () {
        $invokable = new class {
            public function __invoke() {}
        };

        $result = DiscordValueNormalizer::normalize($invokable);

        expect($result)->toStartWith('callable[');
    });

    it('normalizes a Throwable with code block format', function () {
        $exception = new RuntimeException('Something went wrong');

        $result = DiscordValueNormalizer::normalize($exception);

        expect($result)->toContain('RuntimeException')
            ->and($result)->toContain('Something went wrong');
    });

    it('truncates a long exception trace to FIELD_VALUE limit', function () {
        $exception = new RuntimeException(str_repeat('e', 2000));

        $result = DiscordValueNormalizer::normalize($exception);

        expect(mb_strlen($result))->toBeLessThanOrEqual(DiscordLimits::FIELD_VALUE);
    });

    it('normalizes nested arrays with unicode characters without escaping', function () {
        $result = DiscordValueNormalizer::normalize(['name' => 'José']);

        expect($result)->toContain('José');
    });
});
