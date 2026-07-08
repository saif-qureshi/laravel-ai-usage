<?php

namespace BacktikCh\LaravelAiUsage\Listeners;

use BacktikCh\LaravelAiUsage\AiUsageLog;
use BacktikCh\LaravelAiUsage\AiUsageStatus;
use Illuminate\Support\Facades\Log;

class LogPromptingAgent
{
    /**
     * Create a pending AI usage record when an agent prompt begins.
     */
    public function handle(object $event): void
    {
        try {
            $prompt = $event->prompt;

            $agentClass = is_object($prompt) && property_exists($prompt, 'agent')
                ? $prompt->agent::class
                : null;

            $data = [
                'agent_class' => $agentClass,
                'label' => $agentClass ? AiUsageLog::labelFromClass($agentClass) : null,
                'status' => AiUsageStatus::Processing->value,
                'request_meta' => [
                    'invocation_id' => $event->invocationId,
                ],
            ];

            if (config('ai-usage.log_system_prompt', false)
                && is_object($prompt)
                && method_exists($prompt, 'toArray')) {
                $data['prompt_text'] = json_encode($prompt->toArray());
            }

            AiUsageLog::query()->create($data);
        } catch (\Throwable $e) {
            Log::error('[laravel-ai-usage] Failed to log prompting agent.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
