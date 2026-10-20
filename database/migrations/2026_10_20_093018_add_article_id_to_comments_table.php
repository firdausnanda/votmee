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
            $table->foreignUlid('article_id')->nullable()->after('object_id')->constrained('object_articles')->restrictOnDelete();
            $table->index(['article_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['article_id', 'status', 'created_at']);
            $table->dropForeign(['article_id']);
            $table->dropColumn('article_id');
        });
    }
};
