<?php

namespace BacktikCh\LaravelAiUsage;

enum AiUsageStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Failed]);
    }
}
