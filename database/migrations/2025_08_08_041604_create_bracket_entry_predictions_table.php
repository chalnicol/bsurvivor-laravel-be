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
        Schema::create('bce_predictions', function (Blueprint $table) {
           $table->id();
            $table->foreignId('bracket_challenge_entry_id')->constrained('bracket_challenge_entries')->cascadeOnDelete();
            $table->foreignId('matchup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('predicted_winner_team_id')->constrained('teams')->cascadeOnDelete();
            $table->json('teams');
            $table->timestamps();
            $table->unique(['bracket_challenge_entry_id', 'matchup_id']); // One prediction per matchup per entry
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): voidclea
    {
        Schema::dropIfExists('bce_predictions');
    }
};
