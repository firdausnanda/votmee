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
        Schema::table('ranking_entries', function (Blueprint $table) {
            $table->index(['object_id', 'ranking_post_id'], 'ranking_entries_object_post_index');
        });

        Schema::table('object_articles', function (Blueprint $table) {
            $table->index(['object_id', 'created_at'], 'object_articles_object_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('object_articles', function (Blueprint $table) {
            $table->dropIndex('object_articles_object_created_index');
        });

        Schema::table('ranking_entries', function (Blueprint $table) {
            $table->dropIndex('ranking_entries_object_post_index');
        });
    }
};
