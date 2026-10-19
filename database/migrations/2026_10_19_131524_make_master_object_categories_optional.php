<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('objects', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->change();
            $table->index(['normalized_name', 'status'], 'objects_name_status_index');
        });

        Schema::table('object_aliases', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->change();
            $table->index('normalized_alias', 'object_aliases_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('objects')->whereNull('category_id')->exists() || DB::table('object_aliases')->whereNull('category_id')->exists()) {
            throw new RuntimeException('Cannot require object categories while category-independent objects exist.');
        }

        Schema::table('object_aliases', function (Blueprint $table): void {
            $table->dropIndex('object_aliases_name_index');
            $table->foreignId('category_id')->nullable(false)->change();
        });

        Schema::table('objects', function (Blueprint $table): void {
            $table->dropIndex('objects_name_status_index');
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }
};
