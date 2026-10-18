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
        Schema::table('categories', function (Blueprint $table) {
            $table->userstamps();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->userstamps();
        });

        Schema::table('objects', function (Blueprint $table) {
            $table->userstamps();
        });

        Schema::table('ranking_posts', function (Blueprint $table) {
            $table->userstamps();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->userstamps();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->userstamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->userstampSoftDeletes();
        });

        // Backfill existing rows with appropriate user ids
        $defaultAdminId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($defaultAdminId) {
            DB::table('categories')->whereNull('created_by')->update([
                'created_by' => $defaultAdminId,
                'updated_by' => $defaultAdminId,
            ]);

            DB::table('brands')->whereNull('created_by')->update([
                'created_by' => $defaultAdminId,
                'updated_by' => $defaultAdminId,
            ]);
        }

        DB::table('objects')->whereNull('created_by')->whereNotNull('submitted_by')->update([
            'created_by' => DB::raw('submitted_by'),
            'updated_by' => DB::raw('COALESCE(verified_by, submitted_by)'),
        ]);

        DB::table('ranking_posts')->whereNull('created_by')->whereNotNull('creator_id')->update([
            'created_by' => DB::raw('creator_id'),
            'updated_by' => DB::raw('creator_id'),
        ]);

        DB::table('comments')->whereNull('created_by')->whereNotNull('user_id')->update([
            'created_by' => DB::raw('user_id'),
            'updated_by' => DB::raw('user_id'),
        ]);

        DB::table('reports')->whereNull('created_by')->whereNotNull('reporter_id')->update([
            'created_by' => DB::raw('reporter_id'),
            'updated_by' => DB::raw('reporter_id'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropUserstampSoftDeletes());
        Schema::table('reports', fn (Blueprint $table) => $table->dropUserstamps());
        Schema::table('comments', fn (Blueprint $table) => $table->dropUserstamps());
        Schema::table('ranking_posts', fn (Blueprint $table) => $table->dropUserstamps());
        Schema::table('objects', fn (Blueprint $table) => $table->dropUserstamps());
        Schema::table('brands', fn (Blueprint $table) => $table->dropUserstamps());
        Schema::table('categories', fn (Blueprint $table) => $table->dropUserstamps());
    }
};
