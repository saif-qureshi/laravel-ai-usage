<?php

namespace Emilevl\LaravelAiUsage;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AiUsageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ai-usage.php',
            'ai-usage'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ai-usage.php' => config_path('ai-usage.php'),
        ], 'ai-usage-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\PruneCommand::class,
            ]);
        }

        if ($this->autoDiscoverEnabled()) {
            Event::listen(
                \Laravel\Ai\Events\PromptingAgent::class,
                Listeners\LogPromptingAgent::class,
            );

            Event::listen(
                \Laravel\Ai\Events\AgentPrompted::class,
                Listeners\LogAgentPrompted::class,
            );
        }
    }

    private function autoDiscoverEnabled(): bool
    {
        return config('ai-usage.auto_discover', true)
            && class_exists(\Laravel\Ai\Events\AgentPrompted::class);
    }
}
