<?php

namespace BacktikCh\LaravelAiUsage\Filament;

use BacktikCh\LaravelAiUsage\Filament\Resources\AiUsageResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class AiUsagePlugin implements Plugin
{
    public function getId(): string
    {
        return 'laravel-ai-usage';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AiUsageResource::class,
        ]);

        $panel->widgets([
            Widgets\AiUsageStatsWidget::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
