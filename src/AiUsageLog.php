<?php

namespace Emilevl\LaravelAiUsage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AiUsageLog extends Model
{
    protected $fillable = [
        'label',
        'driver',
        'model',
        'agent_class',
        'prompt_tokens',
        'completion_tokens',
        'cache_write_tokens',
        'cache_read_tokens',
        'reasoning_tokens',
        'duration_ms',
        'status',
        'prompt_text',
        'response_text',
        'request_meta',
        'response_meta',
        'owner_type',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiUsageStatus::class,
            'request_meta' => 'array',
            'response_meta' => 'array',
            'prompt_tokens' => 'int',
            'completion_tokens' => 'int',
            'cache_write_tokens' => 'int',
            'cache_read_tokens' => 'int',
            'reasoning_tokens' => 'int',
            'duration_ms' => 'int',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('ai-usage.table', 'ai_usage'));
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function totalTokens(): int
    {
        return $this->prompt_tokens
            + $this->completion_tokens
            + $this->cache_write_tokens
            + $this->cache_read_tokens
            + $this->reasoning_tokens;
    }

    /**
     * Build a human-readable label from the agent class FQCN.
     */
    public static function labelFromClass(string $fqcn): string
    {
        $base = class_basename($fqcn);

        return (string) Str::of($base)
            ->replaceEnd('Agent', '')
            ->snake('-');
    }
}
