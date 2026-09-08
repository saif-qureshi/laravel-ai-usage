<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('ai-usage.table', 'ai_usage'), function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->index()->after('agent_class');
        });
    }

    public function down(): void
    {
        Schema::table(config('ai-usage.table', 'ai_usage'), function (Blueprint $table) {
            $table->dropColumn('account_id');
        });
    }
};
