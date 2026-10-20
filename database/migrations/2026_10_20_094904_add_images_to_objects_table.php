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
        Schema::table('objects', function (Blueprint $table) {
            $table->string('image_path')->nullable();
            $table->string('pending_image_path')->nullable();
            $table->foreignId('pending_image_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pending_image_by');
            $table->dropColumn(['image_path', 'pending_image_path']);
        });
    }
};
