<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('ai-usage.table', 'ai_usage'), function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable()->index();
            $table->string('driver')->nullable();
            $table->string('model')->nullable();
            $table->string('agent_class')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('reasoning_tokens')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status')->default('pending');
            $table->mediumText('prompt_text')->nullable();
            $table->mediumText('response_text')->nullable();
            $table->json('request_meta')->nullable();
            $table->json('response_meta')->nullable();
            $table->nullableMorphs('owner');
            $table->timestamps();

            $table->index('status');
            $table->index('driver');
            $table->index('model');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ai-usage.table', 'ai_usage'));
    }
};
