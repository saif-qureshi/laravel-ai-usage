<?php

namespace Emilevl\LaravelAiUsage\Facades;

use Emilevl\LaravelAiUsage\PendingUsageLog;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PendingUsageLog driver(string $driver)
 * @method static PendingUsageLog model(string $model)
 * @method static PendingUsageLog label(string $label)
 * @method static PendingUsageLog agentClass(string $fqcn)
 * @method static PendingUsageLog tokens(int $prompt, int $completion)
 * @method static PendingUsageLog duration(int $milliseconds)
 * @method static PendingUsageLog prompt(string $text)
 * @method static PendingUsageLog response(string $text)
 * @method static PendingUsageLog status(string $status)
 * @method static PendingUsageLog requestMeta(array $meta)
 * @method static PendingUsageLog responseMeta(array $meta)
 * @method static PendingUsageLog owner(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Emilevl\LaravelAiUsage\AiUsageLog log(array $data = [])
 *
 * @see \Emilevl\LaravelAiUsage\PendingUsageLog
 */
class AiUsage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PendingUsageLog::class;
    }
}
