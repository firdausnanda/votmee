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
        Schema::table('ranking_posts', function (Blueprint $table) {
            $table->string('generator', 20)->nullable()->after('status');
            $table->index(['generator', 'category_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranking_posts', function (Blueprint $table) {
            $table->dropIndex(['generator', 'category_id', 'created_at']);
            $table->dropColumn('generator');
        });
    }
};
