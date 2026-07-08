<?php

namespace BacktikCh\LaravelAiUsage\Filament\Resources\AiUsageResource\Pages;

use BacktikCh\LaravelAiUsage\Filament\Resources\AiUsageResource;
use BacktikCh\LaravelAiUsage\Filament\Widgets\AiUsageSummaryWidget;
use Filament\Resources\Pages\ListRecords;

class ListAiUsage extends ListRecords
{
    protected static string $resource = AiUsageResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AiUsageSummaryWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
