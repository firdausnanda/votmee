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
        Schema::table('wallets', function (Blueprint $table) {
            $table->unsignedInteger('streak_days')->default(0)->after('balance');
            $table->unsignedInteger('longest_streak')->default(0)->after('streak_days');
            $table->date('last_claimed_on')->nullable()->after('longest_streak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['streak_days', 'longest_streak', 'last_claimed_on']);
        });
    }
};
