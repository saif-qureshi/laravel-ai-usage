<?php

namespace BacktikCh\LaravelAiUsage\Filament\Widgets;

use BacktikCh\LaravelAiUsage\AiUsageLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiUsageStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCalls = AiUsageLog::query()->count();

        $totalPromptTokens = (int) AiUsageLog::query()->sum('prompt_tokens');
        $totalCompletionTokens = (int) AiUsageLog::query()->sum('completion_tokens');

        $avgDurationMs = (int) AiUsageLog::query()
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');

        $failedCount = AiUsageLog::query()
            ->where('status', 'failed')
            ->count();

        $models = AiUsageLog::query()
            ->select('model')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('model')
            ->groupBy('model')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'model')
            ->map(fn ($count, $model) => "{$model} ({$count})")
            ->implode(', ') ?: '—';

        return [
            Stat::make('Total AI Calls', $totalCalls)
                ->description($models)
                ->icon('heroicon-o-cpu-chip'),

            Stat::make('Prompt Tokens', number_format($totalPromptTokens))
                ->description('Total prompt tokens consumed')
                ->icon('heroicon-o-arrow-up-circle'),

            Stat::make('Completion Tokens', number_format($totalCompletionTokens))
                ->description('Total completion tokens generated')
                ->icon('heroicon-o-arrow-down-circle'),

            Stat::make('Avg. Duration', $avgDurationMs ? "{$avgDurationMs} ms" : '—')
                ->description("{$failedCount} failed / {$totalCalls} total")
                ->color($failedCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),
        ];
    }
}
