<?php

namespace BacktikCh\LaravelAiUsage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PendingUsageLog
{
    protected array $data = [];

    public function driver(string $driver): static
    {
        $this->data['driver'] = $driver;

        return $this;
    }

    public function model(string $model): static
    {
        $this->data['model'] = $model;

        return $this;
    }

    public function label(string $label): static
    {
        $this->data['label'] = $label;

        return $this;
    }

    public function agentClass(string $fqcn): static
    {
        $this->data['agent_class'] = $fqcn;

        return $this;
    }

    public function tokens(int $prompt, int $completion, int $cacheWrite = 0, int $cacheRead = 0, int $reasoning = 0): static
    {
        $this->data['prompt_tokens'] = $prompt;
        $this->data['completion_tokens'] = $completion;
        $this->data['cache_write_tokens'] = $cacheWrite;
        $this->data['cache_read_tokens'] = $cacheRead;
        $this->data['reasoning_tokens'] = $reasoning;

        return $this;
    }

    public function duration(int $milliseconds): static
    {
        $this->data['duration_ms'] = $milliseconds;

        return $this;
    }

    public function prompt(string $text): static
    {
        $this->data['prompt_text'] = $this->truncateText($text);

        return $this;
    }

    public function response(string $text): static
    {
        if (! config('ai-usage.log_response_text', true)) {
            return $this;
        }

        $this->data['response_text'] = $this->truncateText($text);

        return $this;
    }

    public function status(string $status): static
    {
        $this->data['status'] = $status;

        return $this;
    }

    public function requestMeta(array $meta): static
    {
        $this->data['request_meta'] = array_merge(
            $this->data['request_meta'] ?? [],
            $meta
        );

        return $this;
    }

    public function responseMeta(array $meta): static
    {
        $this->data['response_meta'] = array_merge(
            $this->data['response_meta'] ?? [],
            $meta
        );

        return $this;
    }

    public function owner(Model $model): static
    {
        $this->data['owner_type'] = $model->getMorphClass();
        $this->data['owner_id'] = $model->getKey();

        return $this;
    }

    public function costPrices(array $prices): static
    {
        $this->data['prompt_cost_per_million'] = $prices['prompt'] ?? null;
        $this->data['completion_cost_per_million'] = $prices['completion'] ?? null;
        $this->data['cache_write_cost_per_million'] = $prices['cache_write'] ?? null;
        $this->data['cache_read_cost_per_million'] = $prices['cache_read'] ?? null;
        $this->data['reasoning_cost_per_million'] = $prices['reasoning'] ?? null;

        return $this;
    }

    public function log(array $data = []): AiUsageLog
    {
        $merged = array_merge($this->data, $data);

        // Auto-snapshot prices from config if not already explicitly set
        if (! array_key_exists('prompt_cost_per_million', $merged)) {
            $driver = $merged['driver'] ?? null;
            $model = $merged['model'] ?? null;

            if ($driver && $model) {
                $prices = config("ai-usage.prices.{$driver}.{$model}");
                if (is_array($prices)) {
                    $merged['prompt_cost_per_million'] = $prices['prompt'] ?? null;
                    $merged['completion_cost_per_million'] = $prices['completion'] ?? null;
                    $merged['cache_write_cost_per_million'] = $prices['cache_write'] ?? null;
                    $merged['cache_read_cost_per_million'] = $prices['cache_read'] ?? null;
                    $merged['reasoning_cost_per_million'] = $prices['reasoning'] ?? null;
                }
            }
        }

        return $this->persist($merged);
    }

    protected function persist(array $data): AiUsageLog
    {
        try {
            /** @var AiUsageLog $record */
            $record = AiUsageLog::query()->create($data);

            return $record;
        } catch (\Throwable $e) {
            Log::error('[laravel-ai-usage] Failed to log AI usage.', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return new AiUsageLog($data);
        }
    }

    private function truncateText(string $text): string
    {
        $max = config('ai-usage.max_text_length');

        if ($max !== null) {
            return mb_substr($text, 0, $max);
        }

        return $text;
    }
}
