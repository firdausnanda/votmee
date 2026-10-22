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
        Schema::table('objects', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->index(['submitted_by', 'verified_at']);
        });

        DB::table('objects')->whereIn('status', ['verified', 'merged'])->update(['verified_at' => DB::raw('updated_at')]);

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trusted_at')->nullable()->after('restricted_until');
            $table->timestamp('trust_revoked_at')->nullable()->after('trusted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trusted_at', 'trust_revoked_at']);
        });

        Schema::table('objects', function (Blueprint $table) {
            $table->dropIndex(['submitted_by', 'verified_at']);
            $table->dropColumn('verified_at');
        });
    }
};
