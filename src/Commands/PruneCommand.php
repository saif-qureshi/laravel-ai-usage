<?php

namespace Emilevl\LaravelAiUsage\Commands;

use Emilevl\LaravelAiUsage\AiUsageLog;
use Illuminate\Console\Command;

class PruneCommand extends Command
{
    protected $signature = 'ai-usage:prune {--days=90 : Delete records older than this many days}';

    protected $description = 'Prune old AI usage records from the database';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = AiUsageLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$count} AI usage record(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
