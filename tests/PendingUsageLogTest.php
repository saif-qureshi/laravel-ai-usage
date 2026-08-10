<?php

namespace BacktikCh\LaravelAiUsage\Tests;

use BacktikCh\LaravelAiUsage\PendingUsageLog;

class PendingUsageLogTest extends TestCase
{
    public function test_it_snapshots_prices_for_a_model_name_containing_dots(): void
    {
        $prices = config('ai-usage.prices', []);
        $prices['gemini']['gemini-3.1-flash-lite'] = [
            'prompt' => 0.15,
            'completion' => 0.60,
        ];
        config(['ai-usage.prices' => $prices]);

        $log = (new PendingUsageLog)
            ->driver('gemini')
            ->model('gemini-3.1-flash-lite')
            ->log();

        $this->assertSame(0.15, $log->prompt_cost_per_million);
        $this->assertSame(0.60, $log->completion_cost_per_million);
    }

    public function test_hiding_costs_does_not_stop_price_snapshots(): void
    {
        config(['ai-usage.show_costs' => false]);

        $log = (new PendingUsageLog)
            ->driver('openai')
            ->model('gpt-4o')
            ->tokens(1_000_000, 500_000)
            ->log();

        $this->assertSame(2.5, $log->prompt_cost_per_million);
        $this->assertSame(10.0, $log->completion_cost_per_million);
        $this->assertSame(7.5, $log->estimated_cost);
    }
}
