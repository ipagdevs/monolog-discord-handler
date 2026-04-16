<?php

namespace IpagDevs\Logging;

use Throwable;

final class DiscordValueNormalizer
{
    public static function normalize(mixed $value): string
    {
        return match (true) {
            $value instanceof Throwable => self::exception($value),
            is_callable($value) => self::callable($value),
            is_array($value), is_object($value) => self::json($value),
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => DiscordLimits::truncate(strval($value), DiscordLimits::FIELD_VALUE),
            default => 'null',
        };
    }

    private static function json(mixed $value): string
    {
        try {
            $json = json_encode(
                $value,
                JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_PARTIAL_OUTPUT_ON_ERROR |
                    JSON_INVALID_UTF8_SUBSTITUTE
            );

            if (!$json) {
                return self::fallbackObject($value);
            }

            return DiscordLimits::truncate($json, DiscordLimits::FIELD_VALUE);
        } catch (Throwable) {
            return self::fallbackObject($value);
        }
    }

    private static function fallbackObject(mixed $value): string
    {
        if (is_object($value)) {
            return '[object ' . $value::class . ']';
        }

        return 'unserializable';
    }

    private static function callable(mixed $value): string
    {
        if (is_array($value)) {
            return 'callable[array]';
        }

        if (is_string($value)) {
            return "callable:{$value}";
        }

        if ($value instanceof \Closure) {
            return 'callable[closure]';
        }

        if (is_object($value)) {
            return 'callable[' . $value::class . ']';
        }

        return 'callable';
    }

    private static function exception(Throwable $e): string
    {
        $header = sprintf(
            "**%s**: %s\n**File**: %s:%d",
            $e::class,
            DiscordLimits::truncate($e->getMessage(), DiscordLimits::MESSAGE),
            $e->getFile(),
            $e->getLine()
        );

        $trace = self::reduceTrace($e);

        $content = $header . "\n" . $trace;

        $content = DiscordLimits::truncate($content, DiscordLimits::FIELD_VALUE);

        return $content;
    }

    private static function reduceTrace(Throwable $e, int $limit = 12): string
    {
        $lines = explode("\n", $e->getTraceAsString());

        return implode("\n", array_slice($lines, 0, $limit));
    }
}
