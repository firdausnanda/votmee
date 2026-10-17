<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->string('locale', 2)->default('id');
            $table->string('region_code', 16)->default('ID');
            $table->string('role', 20)->default('user');
            $table->timestamp('restricted_until')->nullable();
        });

        DB::table('users')->whereNull('username')->orderBy('id')->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update(['username' => 'user-'.$user->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'locale', 'region_code', 'role', 'restricted_until']);
        });
    }
};
