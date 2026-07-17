<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('ai-usage.table', 'ai_usage'), function (Blueprint $table) {
            $table->dropIndex(['owner_type', 'owner_id']);
            $table->string('owner_id')->nullable()->change();
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::table(config('ai-usage.table', 'ai_usage'), function (Blueprint $table) {
            $table->dropIndex(['owner_type', 'owner_id']);
            $table->unsignedBigInteger('owner_id')->nullable()->change();
            $table->index(['owner_type', 'owner_id']);
        });
    }
};
