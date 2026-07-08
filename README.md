# Laravel AI Usage

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Track and log AI usage (tokens, cost, duration) for any Laravel application, with optional auto-discovery for `laravel/ai` SDK.

## Requirements

- PHP 8.3+
- Laravel 12.x

## Installation

```bash
composer require emilevl/laravel-ai-usage
```

Publish the migration and config file:

```bash
php artisan vendor:publish --tag=ai-usage-config
php artisan migrate
```

## Configuration

The config file (`config/ai-usage.php`) exposes the following options:

| Key | Default | Description |
|---|---|---|
| `table` | `ai_usage` | Database table name |
| `queue_logs` | `false` | Dispatch log writes as queued jobs |
| `log_system_prompt` | `false` | Store system prompts in the log |
| `log_response_text` | `true` | Store AI response bodies |
| `max_text_length` | `10_000` | Truncate prompt/response text |
| `auto_discover` | `true` | Auto-listen to `laravel/ai` events |

## Usage

### Manual logging

Use the `AiUsage` facade with a fluent builder:

```php
use Emilevl\LaravelAiUsage\Facades\AiUsage;

AiUsage::driver('openai')
    ->model('gpt-4o')
    ->label('summarize-article')
    ->tokens(prompt: 512, completion: 256)
    ->duration(milliseconds: 1200)
    ->prompt('Summarize this article...')
    ->response('The article discusses...')
    ->status('completed')
    ->log();
```

### Attach to a model (polymorphic owner)

```php
AiUsage::driver('openai')
    ->model('gpt-4o')
    ->tokens(300, 150)
    ->owner($user) // Eloquent model
    ->log();
```

### Auto-discovery mode

When enabled (default) and `laravel/ai` is installed, the package automatically listens for `Laravel\Ai\Events\AgentPrompted` and `PromptingAgent` — no code needed.

## Artisan Commands

### Prune old records

```bash
php artisan ai-usage:prune --days=90
```

## Testing

```bash
composer test
```

## License

MIT — see [LICENSE](LICENSE).
