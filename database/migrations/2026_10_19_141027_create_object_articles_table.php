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
        Schema::create('object_articles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('object_id')->constrained('objects')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 20);
            $table->ulid('published_revision_id')->nullable();
            $table->timestamps();
            $table->index(['object_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_articles');
    }
};
