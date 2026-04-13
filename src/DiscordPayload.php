<?php

namespace IpagDevs\Logging;

use JsonSerializable;
use Monolog\LogRecord;

class DiscordPayload implements JsonSerializable
{
    /** @var DiscordField[] */
    private array $fields = [];

    private function __construct(
        private readonly DiscordConfig $config,
        private string $title,
        private string $description,
        private int $color,
        private string $timestamp,
        private string $channel
    ) {}

    public static function from(DiscordConfig $config, LogRecord $record): self
    {
        $payload = new self(
            $config,
            $record->level->getName(),
            DiscordLimits::truncate($record->message, DiscordLimits::DESCRIPTION),
            self::getColor($record->level),
            $record->datetime->format('c'),
            $record->channel
        );

        foreach ($record->context as $key => $value) {
            $payload->addField(
                DiscordField::create($key, $value)
            );
        }

        return $payload;
    }

    public function addField(DiscordField $field): void
    {
        if (count($this->fields) >= DiscordLimits::MAX_FIELDS) {
            return;
        }

        $this->fields[] = $field;
    }

    private static function getColor(\Monolog\Level $level): int
    {
        return match ($level) {
            \Monolog\Level::Debug => 0x95A5A6, // Gray
            \Monolog\Level::Info => 0x3498DB, // Blue
            \Monolog\Level::Notice => 0x1ABC9C, // Teal
            \Monolog\Level::Warning => 0xF1C40F, // Yellow
            \Monolog\Level::Error => 0xE74C3C, // Red
            \Monolog\Level::Critical => 0x992D22, // Dark Red
            \Monolog\Level::Alert => 0xE67E22, // Orange
            \Monolog\Level::Emergency => 0x992D22, // Dark Red
        };
    }

    public function jsonSerialize(): mixed
    {
        if (!$this->config->isEmbedded()) {
            return [
                'content' => "{$this->title}: {$this->description}"
            ];
        }

        return [
            'embeds' => [
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'color' => $this->color,
                    'timestamp' => $this->timestamp,
                    'fields' => DiscordLimits::slice(array_merge(
                        array_map(fn(DiscordField $f) => $f->jsonSerialize(), $this->fields),
                        [DiscordField::create('channel', $this->channel)->jsonSerialize()],
                        [DiscordField::create('timestamp', (new \DateTime())->format('Y-m-d H:i:s'))->jsonSerialize()]
                    )),
                ],
            ],
        ];
    }
}
