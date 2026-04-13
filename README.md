# 📣 Monolog Discord Handler

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ipagdevs/monolog-discord-handler.svg)](https://packagist.org/packages/ipagdevs/monolog-discord-handler)
[![Total Downloads](https://img.shields.io/packagist/dt/ipagdevs/monolog-discord-handler.svg)](https://packagist.org/packages/ipagdevs/monolog-discord-handler)
[![License](https://img.shields.io/packagist/l/ipagdevs/monolog-discord-handler.svg)](https://packagist.org/packages/ipagdevs/monolog-discord-handler)
[![PHP Version](https://img.shields.io/packagist/dependency-v/ipagdevs/monolog-discord-handler/php)](https://packagist.org/packages/ipagdevs/monolog-discord-handler)
[![Build Status](https://github.com/ipagdevs/monolog-discord-handler/actions/workflows/test.yml/badge.svg)](https://github.com/ipagdevs/monolog-discord-handler/actions/workflows/test.yml)

Send Monolog logs directly to a Discord webhook using rich embeds, automatic formatting, and safe value normalization.

---

## 🚀 Installation

```bash
composer require ipagdevs/monolog-discord-handler
```

---

## ⚙️ Usage (Plain Monolog)

```php
use Monolog\Logger;
use Monolog\Level;
use IpagDevs\Logging\DiscordHandler;

$logger = new Logger('app');

$logger->pushHandler(
    new DiscordHandler(
        webhook_url: 'https://discord.com/api/webhooks/xxx',
        level: Level::Debug
    )
);

$logger->error('System error occurred', [
    'user_id' => 123,
    'exception' => new RuntimeException('Critical failure'),
]);
```

---

## 🧩 How it works

The handler automatically:

* Converts logs into Discord **embeds**
* Respects Discord field and message limits
* Normalizes any kind of value:

  * `array` / `object` → pretty JSON
  * `Throwable` → formatted stacktrace block
  * `Closure` → `callable[closure]`
  * string callables → `callable:method`
* Safely truncates large payloads
* Prevents application crashes (fail-safe HTTP handling)

---

## 🧪 Laravel Usage

In `config/logging.php`:

```php
'channels' => [
    'discord' => [
        'driver' => 'monolog',
        'handler' => IpagDevs\Logging\DiscordHandler::class,
        'with' => [
            'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        ],
        'level' => 'debug',
    ],
],
```

In `.env`:

```env
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/xxx
```

---

## 🧠 Example

```php
Log::error('Payment failed', [
    'order_id' => 999,
    'user' => [
        'id' => 1,
        'name' => 'Lucas',
    ],
    'exception' => new RuntimeException('Gateway timeout'),
]);
```

This will be sent to Discord as an embed with:

* log level as title
* message as description
* context automatically converted into fields

---

## 📦 Features

* 📊 Discord embeds support
* 🧠 Smart value normalization
* ⚡ Payload size protection
* 🧾 Exception stacktrace formatting
* 🔇 Fail-safe (never breaks your app)
* 🧩 Monolog v3 compatible

---

## 🧯 Safety

This handler is designed to be safe by default:

* never throws exceptions outside the handler
* never blocks application execution
* ignores HTTP failures silently

---

## 📜 License

MIT
