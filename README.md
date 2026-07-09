# Laravel AI Usage

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Track and log AI usage (tokens, cost, duration) for any Laravel application, with optional auto-discovery for the `laravel/ai` SDK and an optional Filament panel integration.

## Requirements

- PHP 8.3+
- Laravel 12.x

## Installation

```bash
composer require backtik-ch/laravel-ai-usage
```

Publish the config file and run the migrations:

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
| `max_text_length` | `10_000` | Truncate prompt/response text (chars); `null` disables |
| `auto_discover` | `true` | Auto-listen to `laravel/ai` events |
| `prices` | *(see below)* | Token prices (USD / 1 M tokens) per driver & model |

### Token prices

Prices are snapshotted into each log row at write time so historical cost estimates remain accurate after you update the config.

```php
// config/ai-usage.php
'prices' => [
    'openai' => [
        'gpt-4o' => ['prompt' => 2.50, 'completion' => 10.00],
        'gpt-4o-mini' => ['prompt' => 0.15, 'completion' => 0.60],
        // ...
    ],
    'anthropic' => [
        'claude-sonnet-4-5' => [
            'prompt' => 3.00, 'completion' => 15.00,
            'cache_read' => 0.30, 'cache_write' => 3.75,
        ],
        // ...
    ],
    'gemini' => [
        'gemini-2.0-flash' => ['prompt' => 0.10, 'completion' => 0.40],
        // ...
    ],
],
```

Supported price keys: `prompt`, `completion`, `cache_read`, `cache_write`, `reasoning`.

The package ships with indicative prices for common OpenAI, Anthropic and Gemini models. Always verify against your provider's current pricing page.

## Usage

### Manual logging

Use the `AiUsage` facade with a fluent builder:

```php
use BacktikCh\LaravelAiUsage\Facades\AiUsage;

AiUsage::driver('openai')
    ->model('gpt-4o')
    ->label('summarize-article')
    ->agentClass(MyAgent::class)          // optional — FQCN of the agent
    ->tokens(
        prompt: 512,
        completion: 256,
        cacheWrite: 0,                    // optional
        cacheRead: 0,                     // optional
        reasoning: 0,                     // optional
    )
    ->duration(milliseconds: 1200)
    ->prompt('Summarize this article...')
    ->response('The article discusses...')
    ->status('completed')
    ->requestMeta(['temperature' => 0.7]) // optional arbitrary metadata
    ->responseMeta(['finish_reason' => 'stop'])
    ->log();
```

### Attach to a model (polymorphic owner)

```php
AiUsage::driver('openai')
    ->model('gpt-4o')
    ->tokens(300, 150)
    ->owner($user) // any Eloquent model
    ->log();
```

### Override prices at log time

```php
AiUsage::driver('openai')
    ->model('gpt-4o')
    ->tokens(300, 150)
    ->costPrices(['prompt' => 2.50, 'completion' => 10.00])
    ->log();
```

If `costPrices()` is not called, prices are resolved automatically from `config('ai-usage.prices')`.

### Reading the estimated cost

The `AiUsageLog` model exposes an `estimated_cost` computed attribute (float, in USD) derived from the snapshotted per-token prices:

```php
$log = AiUsageLog::find(1);
echo $log->estimated_cost; // e.g. 0.003450
```

Returns `null` when no price data is available.

### Status values

Logs use the `AiUsageStatus` enum: `pending`, `processing`, `completed`, `failed`.

### Auto-discovery mode

When `auto_discover` is `true` (default) and `laravel/ai` is installed, the package automatically listens for `Laravel\Ai\Events\PromptingAgent` and `Laravel\Ai\Events\AgentPrompted` — no extra code needed.

## Filament integration

Requires `filament/filament`. Register the plugin in your panel provider:

```php
use BacktikCh\LaravelAiUsage\Filament\AiUsagePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(AiUsagePlugin::make());
}
```

This registers:

- **`AiUsageResource`** — browsable, filterable list of all log entries with a detail view.
- **`AiUsageStatsWidget`** — stats overview: total calls, prompt/completion tokens, average duration, failed count.

### Summary widget (standalone)

`AiUsageSummaryWidget` is a full-width Livewire widget with a period selector and per-driver / per-model token breakdowns. Add it to any page:

```php
use BacktikCh\LaravelAiUsage\Filament\Widgets\AiUsageSummaryWidget;

protected function getHeaderWidgets(): array
{
    return [AiUsageSummaryWidget::class];
}
```

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
