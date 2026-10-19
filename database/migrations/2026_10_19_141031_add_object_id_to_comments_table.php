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
            $table->foreignUlid('object_id')->nullable()->after('ranking_post_id')->constrained('objects')->restrictOnDelete();
            $table->foreignUlid('ranking_post_id')->nullable()->change();
            $table->index(['object_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['object_id']);
            $table->dropIndex(['object_id', 'status', 'created_at']);
            $table->dropColumn('object_id');
            $table->foreignUlid('ranking_post_id')->nullable(false)->change();
        });
    }
};
