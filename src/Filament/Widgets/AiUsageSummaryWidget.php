<?php

namespace BacktikCh\LaravelAiUsage\Filament\Widgets;

use BacktikCh\LaravelAiUsage\AiUsageLog;
use Filament\Widgets\Widget;

class AiUsageSummaryWidget extends Widget
{
    protected string $view = 'ai-usage::widgets.summary';

    protected int|string|array $columnSpan = 'full';

    public string $period = '7d';

    public function getPeriods(): array
    {
        return [
            'today'     => 'Today',
            'yesterday' => 'Yesterday',
            '7d'        => 'Last 7 days',
            '30d'       => 'Last 30 days',
            '3m'        => 'Last 3 months',
            '6m'        => 'Last 6 months',
            '1y'        => 'Last year',
            'all'       => 'All time',
        ];
    }

    public function updatedPeriod(): void
    {
        $this->dispatch('refreshSummary');
    }

    public function getStatsData(): array
    {
        $query = $this->applyPeriodFilter(AiUsageLog::query());

        return [
            'totalCalls' => (clone $query)->count(),
            'totalTokens' => (clone $query)->get()->sum(fn ($log) => $log->totalTokens()),
            'uniqueDrivers' => (clone $query)->whereNotNull('driver')->distinct('driver')->count('driver'),
            'uniqueModels' => (clone $query)->whereNotNull('model')->distinct('model')->count('model'),
            'avgTokensPerCall' => $query->count() > 0
                ? (int) round((clone $query)->get()->sum(fn ($log) => $log->totalTokens()) / $query->count())
                : 0,
        ];
    }

    public function getTokensByDriver(): array
    {
        $query = $this->applyPeriodFilter(AiUsageLog::query());

        return (clone $query)
            ->select('driver')
            ->selectRaw('SUM(prompt_tokens) as total_prompt_tokens')
            ->selectRaw('SUM(completion_tokens) as total_completion_tokens')
            ->selectRaw('SUM(prompt_tokens + completion_tokens + COALESCE(cache_write_tokens,0) + COALESCE(cache_read_tokens,0) + COALESCE(reasoning_tokens,0)) as total_tokens')
            ->selectRaw('COUNT(*) as total_calls')
            ->selectRaw('AVG(duration_ms) as avg_duration_ms')
            ->whereNotNull('driver')
            ->groupBy('driver')
            ->orderByDesc('total_tokens')
            ->get()
            ->toArray();
    }

    public function getTokensByModel(): array
    {
        $query = $this->applyPeriodFilter(AiUsageLog::query());

        return (clone $query)
            ->select('model')
            ->selectRaw('SUM(prompt_tokens) as total_prompt_tokens')
            ->selectRaw('SUM(completion_tokens) as total_completion_tokens')
            ->selectRaw('SUM(prompt_tokens + completion_tokens + COALESCE(cache_write_tokens,0) + COALESCE(cache_read_tokens,0) + COALESCE(reasoning_tokens,0)) as total_tokens')
            ->selectRaw('COUNT(*) as total_calls')
            ->selectRaw('AVG(duration_ms) as avg_duration_ms')
            ->whereNotNull('model')
            ->groupBy('model')
            ->orderByDesc('total_tokens')
            ->get()
            ->toArray();
    }

    protected function applyPeriodFilter($query)
    {
        return match ($this->period) {
            'today'     => $query->whereDate('created_at', today()),
            'yesterday' => $query->whereDate('created_at', today()->subDay()),
            '7d'        => $query->where('created_at', '>=', now()->subDays(7)),
            '30d'       => $query->where('created_at', '>=', now()->subDays(30)),
            '3m'        => $query->where('created_at', '>=', now()->subMonths(3)),
            '6m'        => $query->where('created_at', '>=', now()->subMonths(6)),
            '1y'        => $query->where('created_at', '>=', now()->subYear()),
            default     => $query,
        };
    }

    public static function canView(): bool
    {
        return true;
    }
}
