<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    |
    | The database table used to store AI usage logs. Change this before
    | running the migration if you need a different table name.
    |
    */
    'table' => 'ai_usage',

    /*
    |--------------------------------------------------------------------------
    | Queue Logs
    |--------------------------------------------------------------------------
    |
    | When true, AI usage records are dispatched as queued jobs instead of
    | being written synchronously. Useful for high-throughput applications.
    |
    */
    'queue_logs' => false,

    /*
    |--------------------------------------------------------------------------
    | Log System Prompt
    |--------------------------------------------------------------------------
    |
    | When true, the full system prompt (agent instructions) is stored in
    | prompt_text. This can significantly increase storage. Default: false.
    |
    */
    'log_system_prompt' => false,

    /*
    |--------------------------------------------------------------------------
    | Log Response Text
    |--------------------------------------------------------------------------
    |
    | When false, the response_text column is left null. Set to false to save
    | storage if you only care about token counts and metadata.
    |
    */
    'log_response_text' => true,

    /*
    |--------------------------------------------------------------------------
    | Maximum Response/Prompt Text Length
    |--------------------------------------------------------------------------
    |
    | Truncate prompt_text and response_text beyond this character count to
    | avoid excessively large rows. Set to null for no truncation.
    |
    */
    'max_text_length' => 10_000,

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery Mode
    |--------------------------------------------------------------------------
    |
    | When true and laravel/ai is installed, the package automatically listens
    | for Laravel\Ai\Events\AgentPrompted and logs all AI agent calls.
    | Set to false to disable auto mode and use only manual logging.
    |
    */
    'auto_discover' => true,

];
