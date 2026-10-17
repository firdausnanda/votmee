<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->timestamps();
        });

        Schema::create('objects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('objects')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('merged_into_id')->nullable()->constrained('objects')->restrictOnDelete();
            $table->string('canonical_name');
            $table->string('normalized_name');
            $table->string('slug')->unique();
            $table->json('identity_attributes')->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->index(['category_id', 'normalized_name', 'status']);
        });

        Schema::create('object_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('object_id')->constrained('objects')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('alias');
            $table->string('normalized_alias');
            $table->timestamps();
            $table->unique(['object_id', 'normalized_alias']);
            $table->index(['category_id', 'normalized_alias']);
        });

        Schema::create('object_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('object_id')->constrained('objects')->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('name');
            $table->timestamps();
            $table->unique(['object_id', 'locale']);
        });

        Schema::create('object_references', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('object_id')->constrained('objects')->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('url', 2048);
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('ranking_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('criteria');
            $table->string('language_code', 2);
            $table->string('region_code', 16)->default('GLOBAL');
            $table->string('status', 24)->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'language_code', 'region_code']);
        });

        Schema::create('ranking_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('ranking_post_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('object_id')->constrained('objects')->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();
            $table->unique(['ranking_post_id', 'object_id']);
            $table->unique(['ranking_post_id', 'position']);
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->timestamps();
        });

        Schema::create('point_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->bigInteger('amount');
            $table->string('type', 24);
            $table->string('idempotency_key')->unique();
            $table->date('claimed_on')->nullable();
            $table->foreignId('reverses_ledger_id')->nullable()->constrained('point_ledger')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'type', 'claimed_on']);
        });

        Schema::create('support_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('ranking_entry_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('object_id')->constrained('objects')->restrictOnDelete();
            $table->foreignId('ledger_id')->unique()->constrained('point_ledger')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status', 16)->default('valid');
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['object_id', 'status', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });

        Schema::create('ranking_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->string('scope_id', 40)->default('GLOBAL');
            $table->foreignUlid('object_id')->constrained('objects')->restrictOnDelete();
            $table->string('rank_type', 20);
            $table->string('period_type', 20);
            $table->timestamp('period_start');
            $table->unsignedBigInteger('score');
            $table->timestamps();
            $table->unique(['scope', 'scope_id', 'object_id', 'rank_type', 'period_type', 'period_start'], 'ranking_aggregates_identity_unique');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('ranking_post_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->restrictOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('visible');
            $table->timestamps();
            $table->index(['ranking_post_id', 'status', 'created_at']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->string('target_type', 24);
            $table->string('target_id', 40);
            $table->text('reason');
            $table->string('status', 24)->default('open');
            $table->timestamps();
            $table->index(['target_type', 'target_id', 'status']);
        });

        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('target_type', 24);
            $table->string('target_id', 40);
            $table->string('action', 40);
            $table->text('reason');
            $table->timestamps();
        });

        Schema::create('object_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('source_id')->constrained('objects')->restrictOnDelete();
            $table->foreignUlid('target_id')->constrained('objects')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('source_status', 24);
            $table->text('reason');
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('target_type', 40);
            $table->string('target_id', 40);
            $table->json('details')->nullable();
            $table->timestamp('created_at');
            $table->index(['target_type', 'target_id', 'created_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('objects', fn (Blueprint $table) => $table->fullText('canonical_name'));
            Schema::table('object_aliases', fn (Blueprint $table) => $table->fullText('alias'));
            Schema::table('ranking_posts', fn (Blueprint $table) => $table->fullText(['title', 'criteria']));
        }
    }

    public function down(): void
    {
        foreach (['audit_logs', 'object_merges', 'moderation_actions', 'reports', 'comments', 'ranking_aggregates', 'support_events', 'point_ledger', 'wallets', 'ranking_entries', 'ranking_posts', 'object_references', 'object_translations', 'object_aliases', 'objects', 'brands', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
