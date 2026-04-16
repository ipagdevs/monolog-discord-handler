<?php

use IpagDevs\Logging\DiscordField;
use IpagDevs\Logging\DiscordLimits;

describe('DiscordField::create', function () {
    it('creates a field with string value and inline true', function () {
        $field = DiscordField::create('key', 'value');
        $data = $field->jsonSerialize();

        expect($data['name'])->toBe(ucfirst('key'))
            ->and($data['value'])->toBe('value')
            ->and($data['inline'])->toBeTrue();
    });

    it('creates a field with a Throwable value and inline false', function () {
        $exception = new RuntimeException('error');
        $field = DiscordField::create('error', $exception);
        $data = $field->jsonSerialize();

        expect($data['inline'])->toBeFalse()
            ->and($data['value'])->toContain('RuntimeException');
    });

    it('truncates the key when it exceeds FIELD_NAME limit', function () {
        $longKey = str_repeat('k', 300);
        $field = DiscordField::create($longKey, 'value');
        $data = $field->jsonSerialize();

        expect(mb_strlen($data['name']))->toBe(DiscordLimits::FIELD_NAME)
            ->and($data['name'])->toEndWith('...');
    });

    it('normalizes bool true value', function () {
        $field = DiscordField::create('active', true);
        $data = $field->jsonSerialize();

        expect($data['value'])->toBe('true')
            ->and($data['inline'])->toBeTrue();
    });

    it('normalizes bool false value', function () {
        $field = DiscordField::create('active', false);
        $data = $field->jsonSerialize();

        expect($data['value'])->toBe('false');
    });

    it('normalizes null value', function () {
        $field = DiscordField::create('data', null);
        $data = $field->jsonSerialize();

        expect($data['value'])->toBe('null');
    });

    it('normalizes array value to JSON', function () {
        $field = DiscordField::create('context', ['foo' => 'bar']);
        $data = $field->jsonSerialize();

        expect($data['value'])->toContain('"foo"')
            ->and($data['value'])->toContain('"bar"');
    });
});

describe('DiscordField::jsonSerialize', function () {
    it('returns an array with name, value and inline keys', function () {
        $field = DiscordField::create('test', 'data');
        $data = $field->jsonSerialize();

        expect($data)->toHaveKeys(['name', 'value', 'inline']);
    });
});
