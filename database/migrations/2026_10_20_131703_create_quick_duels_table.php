<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_duels', function (Blueprint $table) {
            $table->id();
            $table->date('duel_date');
            $table->foreignUlid('ranking_post_id')->constrained()->restrictOnDelete();
            $table->foreignId('entry_a_id')->constrained('ranking_entries')->restrictOnDelete();
            $table->foreignId('entry_b_id')->constrained('ranking_entries')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['duel_date', 'ranking_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_duels');
    }
};
