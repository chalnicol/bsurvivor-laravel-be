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
        Schema::create('bracket_challenge_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bracket_challenge_id')->constrained('bracket_challenges')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->json('entry_data')->nullable(); // Store user's entry data in JSON format   
            // $table->boolean('is_winner')->default(false); // Whether the user won the challenge 
            $table->string('status')->default('pending');
            $table->string('slug')->unique();
            $table->timestamps();

            $table->unique(['user_id', 'bracket_challenge_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bracket_challenge_entry');
    }
};
