<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mission_completions', function (Blueprint $table) {
            $table->string('period', 10)->default('daily')->after('mission');
            $table->index(['user_id', 'period', 'completed_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mission_completions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'period', 'completed_on']);
            $table->dropColumn('period');
        });
    }
};
