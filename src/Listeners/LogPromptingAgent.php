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

            $maxLength = config('ai-usage.max_text_length');

            if (config('ai-usage.log_system_prompt', false)
                && is_object($prompt)
                && property_exists($prompt, 'agent')
                && method_exists($prompt->agent, 'instructions')) {
                $instructions = (string) $prompt->agent->instructions();
                $data['prompt_text'] = $maxLength !== null
                    ? mb_substr($instructions, 0, $maxLength)
                    : $instructions;
            }

            if (config('ai-usage.log_user_prompt', false)
                && is_object($prompt)
                && property_exists($prompt, 'prompt')) {
                $userPrompt = (string) $prompt->prompt;
                $data['user_prompt_text'] = $maxLength !== null
                    ? mb_substr($userPrompt, 0, $maxLength)
                    : $userPrompt;
            }

            AiUsageLog::query()->create($data);
        } catch (\Throwable $e) {
            Log::error('[laravel-ai-usage] Failed to log prompting agent.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
