<?php

namespace BacktikCh\LaravelAiUsage;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use LogicException;

class AiUsageLog extends Model
{
    protected $fillable = [
        'label',
        'driver',
        'model',
        'agent_class',
        'account_id',
        'prompt_tokens',
        'completion_tokens',
        'cache_write_tokens',
        'cache_read_tokens',
        'reasoning_tokens',
        'duration_ms',
        'status',
        'prompt_text',
        'user_prompt_text',
        'response_text',
        'request_meta',
        'response_meta',
        'owner_type',
        'owner_id',
        'prompt_cost_per_million',
        'completion_cost_per_million',
        'cache_write_cost_per_million',
        'cache_read_cost_per_million',
        'reasoning_cost_per_million',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiUsageStatus::class,
            'account_id' => 'integer',
            'request_meta' => 'array',
            'response_meta' => 'array',
            'prompt_tokens' => 'int',
            'completion_tokens' => 'int',
            'cache_write_tokens' => 'int',
            'cache_read_tokens' => 'int',
            'reasoning_tokens' => 'int',
            'duration_ms' => 'int',
            'prompt_cost_per_million' => 'float',
            'completion_cost_per_million' => 'float',
            'cache_write_cost_per_million' => 'float',
            'cache_read_cost_per_million' => 'float',
            'reasoning_cost_per_million' => 'float',
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

    public function account(): BelongsTo
    {
        $modelClass = static::accountModelClass();

        if ($modelClass === null) {
            throw new LogicException('Configure ai-usage.account.model before using the account relationship.');
        }

        return $this->belongsTo($modelClass, 'account_id');
    }

    public function accountDisplayName(): string
    {
        if ($this->account_id === null) {
            return '-';
        }

        $account = static::accountModelClass() ? $this->account : null;
        $title = $account?->getAttribute(static::accountTitleAttribute());

        return filled($title)
            ? $title.' (#'.$this->account_id.')'
            : 'Account #'.$this->account_id;
    }

    public static function accountLabels(iterable $accountIds): array
    {
        $accountIds = collect($accountIds)
            ->filter(fn (mixed $accountId): bool => is_numeric($accountId))
            ->map(fn (mixed $accountId): int => (int) $accountId)
            ->unique()
            ->values();

        if ($accountIds->isEmpty()) {
            return [];
        }

        $modelClass = static::accountModelClass();
        if ($modelClass === null) {
            return $accountIds
                ->mapWithKeys(fn (int $accountId): array => [$accountId => 'Account #'.$accountId])
                ->all();
        }

        $model = new $modelClass;
        $keyName = $model->getKeyName();
        $titleAttribute = static::accountTitleAttribute();
        $accounts = $model->newQuery()
            ->whereIn($keyName, $accountIds)
            ->get([$keyName, $titleAttribute])
            ->keyBy($keyName);

        return $accountIds
            ->mapWithKeys(function (int $accountId) use ($accounts, $titleAttribute): array {
                $title = $accounts->get($accountId)?->getAttribute($titleAttribute);

                return [
                    $accountId => filled($title)
                        ? $title.' (#'.$accountId.')'
                        : 'Account #'.$accountId,
                ];
            })
            ->all();
    }

    public static function accountModelClass(): ?string
    {
        $modelClass = config('ai-usage.account.model');

        return is_string($modelClass) && is_a($modelClass, Model::class, true)
            ? $modelClass
            : null;
    }

    public static function accountTitleAttribute(): string
    {
        $attribute = config('ai-usage.account.title_attribute', 'name');

        return is_string($attribute) && $attribute !== '' ? $attribute : 'name';
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    protected function estimatedCost(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                $cost = 0.0;
                $hasPrices = false;

                $pairs = [
                    ['prompt_tokens', 'prompt_cost_per_million'],
                    ['completion_tokens', 'completion_cost_per_million'],
                    ['cache_write_tokens', 'cache_write_cost_per_million'],
                    ['cache_read_tokens', 'cache_read_cost_per_million'],
                    ['reasoning_tokens', 'reasoning_cost_per_million'],
                ];

                foreach ($pairs as [$tokenField, $priceField]) {
                    $price = $this->getAttribute($priceField);
                    if ($price !== null) {
                        $hasPrices = true;
                        $cost += ($this->getAttribute($tokenField) ?? 0) * $price / 1_000_000;
                    }
                }

                return $hasPrices ? round($cost, 8) : null;
            }
        );
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
