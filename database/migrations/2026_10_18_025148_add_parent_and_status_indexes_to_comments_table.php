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
        Schema::table('comments', function (Blueprint $table) {
            $table->index(['ranking_post_id', 'parent_id', 'status', 'created_at'], 'comments_post_parent_status_idx');
            $table->index(['parent_id', 'status'], 'comments_parent_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_parent_status_idx');
            $table->dropIndex('comments_parent_status_idx');
        });
    }
};
