<?php

namespace BacktikCh\LaravelAiUsage\Commands;

use BacktikCh\LaravelAiUsage\AiUsageLog;
use BacktikCh\LaravelAiUsage\AiUsageStatus;
use Illuminate\Console\Command;

class MarkStaleAsFailedCommand extends Command
{
    protected $signature = 'ai-usage:mark-stale-failed
                            {--minutes= : Mark processing records older than this many minutes as failed (overrides config)}';

    protected $description = 'Mark stale processing AI usage records as failed';

    public function handle(): int
    {
        $minutes = $this->option('minutes') !== null
            ? (int) $this->option('minutes')
            : (int) config('ai-usage.stale_timeout_minutes', 60);

        $cutoff = now()->subMinutes($minutes);

        $count = AiUsageLog::query()
            ->where('status', AiUsageStatus::Processing->value)
            ->where('created_at', '<', $cutoff)
            ->update(['status' => AiUsageStatus::Failed->value]);

        $this->info("Marked {$count} stale AI usage record(s) as failed (older than {$minutes} minutes).");

        return self::SUCCESS;
    }
}
