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
        Schema::create('object_content_revisions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('object_id')->constrained('objects')->restrictOnDelete();
            $table->foreignUlid('article_id')->nullable()->constrained('object_articles')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->json('sources');
            $table->string('language_code', 2);
            $table->string('status', 20)->default('pending');
            $table->text('review_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['object_id', 'article_id', 'status', 'created_at'], 'content_revisions_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_content_revisions');
    }
};
