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
        Schema::create('content_image_revision', function (Blueprint $table) {
            $table->foreignId('content_image_id')->constrained('content_images')->cascadeOnDelete();
            $table->foreignUlid('revision_id')->constrained('object_content_revisions')->cascadeOnDelete();

            $table->primary(['content_image_id', 'revision_id']);
            $table->index('revision_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_image_revision');
    }
};
