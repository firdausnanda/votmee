<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_events', function (Blueprint $table) {
            $table->unsignedBigInteger('ledger_id')->nullable()->change();
            $table->string('source', 16)->default('support')->after('amount');
            $table->date('vote_date')->nullable()->after('source');
            $table->unique(['user_id', 'vote_date']);
            $table->index(['source', 'status', 'vote_date']);
        });
    }

    public function down(): void
    {
        Schema::table('support_events', function (Blueprint $table) {
            $table->dropIndex(['source', 'status', 'vote_date']);
            $table->dropUnique(['user_id', 'vote_date']);
            $table->dropColumn(['source', 'vote_date']);
        });
    }
};
