<?php

namespace IpagDevs\Logging;

final class DiscordLimits
{
    public const FIELD_NAME = 256;
    public const FIELD_VALUE = 1024;
    public const MESSAGE = 2048;
    public const DESCRIPTION = 4096;
    public const MAX_FIELDS = 25;

    /**
     * Truncates a string to the specified maximum limit, appending "..." if truncation occurs.
     *
     * @param string $value
     * @param int $maxLimit
     * @return string
     */
    public static function truncate(string $value, int $maxLimit): string
    {
        return mb_strlen($value) <= $maxLimit
            ? $value
            : mb_substr($value, 0, $maxLimit - 3) . '...';
    }

    /**
     * Slices the array to the maximum number of fields allowed by Discord.
     *
     * @param array<array-key,mixed> $array
     * @return array<array-key,mixed>
     */
    public static function slice(array $array): array
    {
        return array_slice($array, 0, self::MAX_FIELDS);
    }
}
