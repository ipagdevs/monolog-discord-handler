<?php

namespace IpagDevs\Logging;

use Throwable;
use JsonSerializable;

class DiscordField implements JsonSerializable
{
    private function __construct(private string $name, private string $value, private bool $inline = true) {}

    public static function create(string $key, mixed $value): self
    {
        return new self(
            ucfirst(DiscordLimits::truncate($key, DiscordLimits::FIELD_NAME)),
            DiscordValueNormalizer::normalize($value),
            $value instanceof Throwable ? false : true
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'inline' => $this->inline,
        ];
    }
}
