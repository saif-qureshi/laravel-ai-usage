<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('ai-usage.table', 'ai_usage'), function (Blueprint $table) {
            $table->decimal('prompt_cost_per_million', 10, 6)->nullable()->after('reasoning_tokens');
            $table->decimal('completion_cost_per_million', 10, 6)->nullable()->after('prompt_cost_per_million');
            $table->decimal('cache_write_cost_per_million', 10, 6)->nullable()->after('completion_cost_per_million');
            $table->decimal('cache_read_cost_per_million', 10, 6)->nullable()->after('cache_write_cost_per_million');
            $table->decimal('reasoning_cost_per_million', 10, 6)->nullable()->after('cache_read_cost_per_million');
        });
    }

    public function down(): void
    {
        Schema::table(config('ai-usage.table', 'ai_usage'), function (Blueprint $table) {
            $table->dropColumn([
                'prompt_cost_per_million',
                'completion_cost_per_million',
                'cache_write_cost_per_million',
                'cache_read_cost_per_million',
                'reasoning_cost_per_million',
            ]);
        });
    }
};
